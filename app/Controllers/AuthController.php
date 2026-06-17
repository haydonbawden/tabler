<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Services\ActivityLogger;
use App\Services\RateLimiter;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login', ['title' => 'Sign in'], 'layouts/guest');
    }

    public function login(): void
    {
        $email = strtolower(trim((string) $this->input('email')));
        $key = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ':' . $email;
        $limiter = new RateLimiter();
        if ($limiter->tooManyAttempts($key, 5, 900)) {
            Session::flash('error', 'Too many login attempts. Please wait and try again.');
            redirect('/login');
        }

        if (!Auth::attempt($email, (string) $this->input('password'))) {
            $limiter->hit($key, 900);
            (new ActivityLogger())->log('login_failed', 'Failed login attempt.', ['email' => $email]);
            Session::flash('error', 'The supplied credentials were not recognised.');
            redirect('/login');
        }

        $limiter->clear($key);
        $user = Auth::user();
        redirect(match ($user['role']) {
            'auditor' => '/auditor/audits',
            'client' => '/client/profile',
            default => '/admin/clients',
        });
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }

    public function registerForm(): void
    {
        $this->view('auth/register', ['title' => 'Register'], 'layouts/guest');
    }

    public function register(): void
    {
        $name = trim((string) $this->input('name'));
        $email = strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$this->strongPassword($password)) {
            Session::flash('error', 'Enter a valid name, email and a password of at least 10 characters with upper, lower, number and symbol.');
            redirect('/register');
        }

        $contact = $this->db()->first('select contacts.*, clients.id as client_id from contacts join clients on clients.id = contacts.client_id where contacts.email = ? and contacts.is_active = 1 limit 1', [$email]);
        $token = bin2hex(random_bytes(32));
        if ($contact) {
            $this->db()->statement(
                'insert into users (name, email, password_hash, role, status, verification_token, created_at, updated_at)
                 values (?, ?, ?, ?, ?, ?, now(), now())
                 on duplicate key update name = values(name), verification_token = values(verification_token), updated_at = now()',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT), 'client', 'invited', $token]
            );
            $userId = (int) $this->db()->scalar('select id from users where email = ?', [$email]);
            $this->db()->statement(
                'insert ignore into client_user (client_id, user_id, role_for_client, approved_at, created_at, updated_at) values (?, ?, ?, now(), now(), now())',
                [$contact['client_id'], $userId, 'representative']
            );
            (new ActivityLogger())->log('user_registration', 'Client representative matched to existing contact.', ['client_id' => $contact['client_id']]);
            Session::flash('success', 'Registration received. Use the verification link shown for local testing: /verify-email?token=' . $token);
        } else {
            $this->db()->statement(
                'insert into pending_registrations (name, email, requested_client_name, token, created_at, updated_at)
                 values (?, ?, ?, ?, now(), now())
                 on duplicate key update name = values(name), requested_client_name = values(requested_client_name), token = values(token), updated_at = now()',
                [$name, $email, $this->input('client_name'), $token]
            );
            Session::flash('success', 'Registration request submitted for admin approval.');
        }

        redirect('/login');
    }

    public function verifyEmail(): void
    {
        $token = (string) $this->input('token');
        $user = $this->db()->first('select id from users where verification_token = ?', [$token]);
        if (!$user) {
            Session::flash('error', 'Verification token was not found.');
            redirect('/login');
        }
        $this->db()->statement('update users set status = ?, email_verified_at = now(), verification_token = null, updated_at = now() where id = ?', ['active', $user['id']]);
        Session::flash('success', 'Email verified. You can now sign in.');
        redirect('/login');
    }

    public function forgotPasswordForm(): void
    {
        $this->view('auth/forgot-password', ['title' => 'Forgot password'], 'layouts/guest');
    }

    public function forgotPassword(): void
    {
        $email = strtolower(trim((string) $this->input('email')));
        $token = bin2hex(random_bytes(32));
        $this->db()->statement('update users set reset_token = ?, reset_token_expires_at = date_add(now(), interval 1 hour), updated_at = now() where email = ?', [$token, $email]);
        Session::flash('success', 'If the account exists, a reset token has been created. Local testing link: /reset-password?token=' . $token);
        redirect('/forgot-password');
    }

    public function resetPasswordForm(): void
    {
        $this->view('auth/reset-password', ['title' => 'Reset password', 'token' => $this->input('token')], 'layouts/guest');
    }

    public function resetPassword(): void
    {
        $token = (string) $this->input('token');
        $password = (string) $this->input('password');
        if (!$this->strongPassword($password)) {
            Session::flash('error', 'Choose a stronger password.');
            redirect('/reset-password?token=' . urlencode($token));
        }
        $user = $this->db()->first('select id from users where reset_token = ? and reset_token_expires_at > now()', [$token]);
        if ($user) {
            $this->db()->statement('update users set password_hash = ?, reset_token = null, reset_token_expires_at = null, updated_at = now() where id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        Session::flash('success', 'Password updated if the token was valid.');
        redirect('/login');
    }

    private function strongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }
}
