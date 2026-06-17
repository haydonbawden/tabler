document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const escapeSelector = (value) => {
    if (window.CSS?.escape) return CSS.escape(value);
    return String(value).replace(/["\\]/g, '\\$&');
  };
  const parseJson = (value, fallback = {}) => {
    try {
      return JSON.parse(value || '');
    } catch {
      return fallback;
    }
  };

  const postForm = (url, data) => {
    if (!csrf) return Promise.resolve();
    const body = new URLSearchParams({ _token: csrf, ...data });
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
      body,
    }).catch(() => {});
  };

  document.querySelectorAll('[data-enhanced-table]').forEach((root) => {
    const table = root.querySelector('table');
    const tbody = root.querySelector('tbody');
    if (!table || !tbody) return;

    const tableName = root.dataset.tableName || document.title || 'records';
    const rows = Array.from(tbody.querySelectorAll('tr')).filter((row) => !row.hasAttribute('data-empty-row'));
    const search = root.querySelector('[data-table-search]');
    const summary = root.querySelector('[data-table-summary]');
    const pagination = root.querySelector('[data-table-pagination]');
    const pageSizeLabel = root.querySelector('[data-table-page-size-label]');
    const total = root.querySelector('[data-table-total]');
    const filterControls = Array.from(root.querySelectorAll('[data-table-filter]'));
    const columnToggles = Array.from(root.querySelectorAll('[data-table-column-toggle]'));
    const bulkButton = root.querySelector('[data-table-bulk-button]');
    const preferences = parseJson(root.dataset.tablePreferences, {});
    const activeView = parseJson(root.dataset.activeView, {});
    let pageSize = Number(preferences.pageSize || pageSizeLabel?.textContent || 20);
    let page = 1;
    let sortIndex = Number.isInteger(preferences.sortIndex) ? preferences.sortIndex : null;
    let sortDirection = Number(preferences.sortDirection || 1);
    let filtered = [...rows];
    let saveTimer = null;

    const emptyRow = document.createElement('tr');
    emptyRow.hidden = true;
    emptyRow.innerHTML = `<td colspan="${table.tHead.rows[0].children.length}">
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-search-off"></i></div>
        <p class="empty-title">No matching records</p>
        <p class="empty-subtitle text-secondary">Adjust the search term or clear the current filters.</p>
      </div>
    </td>`;
    tbody.appendChild(emptyRow);

    const dataCell = (row, key) => row.querySelector(`[data-column-key="${escapeSelector(key)}"]`);
    const textFor = (row, index) => (row.children[index + 1]?.textContent || '').trim().toLowerCase();
    const selectedRows = () => rows.filter((row) => row.querySelector('.table-selectable-check')?.checked);

    const currentColumns = () => Object.fromEntries(columnToggles.map((toggle) => [toggle.dataset.tableColumnToggle, toggle.checked]));
    const currentFilters = () => Object.fromEntries(filterControls.map((control) => [control.dataset.tableFilter, control.value]));
    const currentPreferences = () => ({
      pageSize,
      sortIndex,
      sortDirection,
      columns: currentColumns(),
      filters: currentFilters(),
      search: search?.value || '',
    });

    const schedulePreferenceSave = () => {
      window.clearTimeout(saveTimer);
      saveTimer = window.setTimeout(() => {
        postForm('/table/preferences', {
          table: tableName,
          preferences: JSON.stringify(currentPreferences()),
        });
      }, 400);
    };

    const setColumnVisible = (key, visible) => {
      root.querySelectorAll(`[data-column-key="${escapeSelector(key)}"]`).forEach((cell) => {
        cell.hidden = !visible;
      });
    };

    const applyColumnState = (columns = {}) => {
      columnToggles.forEach((toggle) => {
        const key = toggle.dataset.tableColumnToggle;
        const visible = columns[key] !== false;
        toggle.checked = visible;
        setColumnVisible(key, visible);
      });
    };

    const applyFilterState = (filters = {}) => {
      filterControls.forEach((control) => {
        if (filters[control.dataset.tableFilter] !== undefined) {
          control.value = filters[control.dataset.tableFilter];
        }
      });
    };

    const applyUrlState = () => {
      const params = new URLSearchParams(window.location.search);
      ['status', 'role', 'view'].forEach((key) => {
        const value = params.get(key);
        if (!value) return;
        filterControls
          .filter((control) => control.dataset.tableFilter === key || control.dataset.filterColumn === key || control.dataset.tableFilter === 'computed_status')
          .forEach((control) => {
            if (Array.from(control.options || []).some((option) => option.value === value)) {
              control.value = value;
            }
          });
      });
      if (params.get('view') === 'expiring_90') {
        filterControls.filter((control) => control.dataset.tableFilter === 'computed_status').forEach((control) => { control.value = 'expiring_soon'; });
      }
    };

    const applyInitialState = () => {
      if (preferences.search && search) search.value = preferences.search;
      applyColumnState(activeView.columns || preferences.columns || {});
      applyFilterState(activeView.filters || preferences.filters || {});
      applyUrlState();
      if (activeView.sort) {
        sortIndex = Number.isInteger(activeView.sort.index) ? activeView.sort.index : sortIndex;
        sortDirection = Number(activeView.sort.direction || sortDirection);
      }
      if (pageSizeLabel) pageSizeLabel.textContent = String(pageSize);
    };

    const csvFor = (sourceRows) => {
      const cells = (row) => Array.from(row.children)
        .slice(1)
        .filter((cell) => !cell.hidden)
        .map((cell) => `"${cell.textContent.trim().replace(/"/g, '""')}"`)
        .join(',');
      const header = cells(table.tHead.rows[0]);
      const body = sourceRows.map(cells).join('\n');
      return `${header}\n${body}`;
    };

    const downloadCsv = (csv) => {
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `${tableName}.csv`;
      link.click();
      URL.revokeObjectURL(link.href);
    };

    const downloadExcel = (sourceRows) => {
      const tableHtml = document.createElement('table');
      const header = table.tHead.rows[0].cloneNode(true);
      header.querySelectorAll('input,button').forEach((control) => {
        const cell = control.closest('th,td');
        if (cell) cell.textContent = cell.textContent.trim();
      });
      header.querySelectorAll('[hidden]').forEach((cell) => cell.remove());
      tableHtml.appendChild(document.createElement('thead')).appendChild(header);
      const body = tableHtml.appendChild(document.createElement('tbody'));
      sourceRows.forEach((row) => {
        const clone = row.cloneNode(true);
        clone.querySelectorAll('input,button').forEach((control) => control.closest('th,td')?.remove());
        clone.querySelectorAll('[hidden]').forEach((cell) => cell.remove());
        body.appendChild(clone);
      });
      const blob = new Blob([`\ufeff${tableHtml.outerHTML}`], { type: 'application/vnd.ms-excel;charset=utf-8' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `${tableName}.xls`;
      link.click();
      URL.revokeObjectURL(link.href);
    };

    const renderPagination = (pages) => {
      if (!pagination) return;
      pagination.innerHTML = '';
      const add = (label, target, active = false, disabled = false) => {
        const item = document.createElement('li');
        item.className = `page-item${active ? ' active' : ''}${disabled ? ' disabled' : ''}`;
        const button = document.createElement('button');
        button.className = 'page-link';
        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', () => {
          page = target;
          render();
        });
        item.appendChild(button);
        pagination.appendChild(item);
      };

      add('<', Math.max(1, page - 1), false, page === 1);
      const start = Math.max(1, page - 2);
      const end = Math.min(pages, page + 2);
      for (let i = start; i <= end; i += 1) add(String(i), i, i === page);
      add('>', Math.min(pages, page + 1), false, page === pages);
    };

    const filterMatches = (row) => filterControls.every((control) => {
      const value = control.value.trim().toLowerCase();
      if (!value) return true;
      const column = control.dataset.filterColumn;
      const cellValue = (dataCell(row, column)?.dataset.filterValue || '').trim().toLowerCase();
      if (control.dataset.filterType === 'select') return cellValue === value;
      if (control.dataset.filterType === 'date_from') return !cellValue || cellValue >= value;
      if (control.dataset.filterType === 'date_to') return !cellValue || cellValue <= value;
      return cellValue.includes(value);
    });

    const updateBulkState = () => {
      const count = selectedRows().length;
      if (bulkButton) {
        bulkButton.disabled = count === 0;
        bulkButton.innerHTML = `<i class="ti ti-checklist me-1"></i>${count ? `${count} selected` : 'Selected'}`;
      }
    };

    const render = () => {
      const term = (search?.value || '').trim().toLowerCase();
      filtered = rows.filter((row) => row.textContent.toLowerCase().includes(term) && filterMatches(row));

      if (sortIndex !== null) {
        filtered.sort((a, b) => textFor(a, sortIndex).localeCompare(textFor(b, sortIndex), undefined, { numeric: true }) * sortDirection);
      }

      const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
      page = Math.min(page, pages);
      const start = (page - 1) * pageSize;
      const visible = new Set(filtered.slice(start, start + pageSize));

      rows.forEach((row) => {
        row.hidden = !visible.has(row);
      });
      emptyRow.hidden = filtered.length !== 0 || rows.length === 0;

      if (summary) {
        const from = filtered.length ? start + 1 : 0;
        const to = Math.min(start + pageSize, filtered.length);
        summary.innerHTML = `Showing <strong>${from}</strong> to <strong>${to}</strong> of <strong>${filtered.length}</strong> entries`;
      }
      if (total) total.textContent = String(filtered.length);
      renderPagination(pages);
      updateBulkState();
    };

    root.querySelectorAll('[data-table-sort]').forEach((button) => {
      button.addEventListener('click', () => {
        const nextIndex = Number(button.dataset.tableSort);
        sortDirection = sortIndex === nextIndex ? sortDirection * -1 : 1;
        sortIndex = nextIndex;
        root.querySelectorAll('[data-table-sort]').forEach((item) => item.classList.remove('asc', 'desc'));
        button.classList.add(sortDirection === 1 ? 'asc' : 'desc');
        render();
        schedulePreferenceSave();
      });
    });

    search?.addEventListener('input', () => {
      page = 1;
      render();
      schedulePreferenceSave();
    });

    filterControls.forEach((control) => {
      control.addEventListener('input', () => {
        page = 1;
        render();
        schedulePreferenceSave();
      });
      control.addEventListener('change', () => {
        page = 1;
        render();
        schedulePreferenceSave();
      });
    });

    columnToggles.forEach((toggle) => {
      toggle.addEventListener('change', () => {
        setColumnVisible(toggle.dataset.tableColumnToggle, toggle.checked);
        render();
        schedulePreferenceSave();
      });
    });

    root.querySelector('[data-table-clear]')?.addEventListener('click', () => {
      if (search) search.value = '';
      filterControls.forEach((control) => { control.value = ''; });
      sortIndex = null;
      sortDirection = 1;
      page = 1;
      root.querySelectorAll('[data-table-sort]').forEach((item) => item.classList.remove('asc', 'desc'));
      render();
      schedulePreferenceSave();
    });

    root.querySelector('[data-table-save-view]')?.addEventListener('click', async () => {
      const name = window.prompt('Save current list view as');
      if (!name) return;
      await postForm('/table/views', {
        table: tableName,
        name,
        scope: 'private',
        payload: JSON.stringify({
          filters: currentFilters(),
          columns: currentColumns(),
          sort: { index: sortIndex, direction: sortDirection },
        }),
      });
      window.location.reload();
    });

    root.querySelectorAll('[data-table-page-size]').forEach((button) => {
      button.addEventListener('click', () => {
        pageSize = Number(button.dataset.tablePageSize || 20);
        if (pageSizeLabel) pageSizeLabel.textContent = String(pageSize);
        page = 1;
        render();
        schedulePreferenceSave();
      });
    });

    root.querySelector('[data-table-select-all]')?.addEventListener('change', (event) => {
      rows.filter((row) => !row.hidden).forEach((row) => {
        const checkbox = row.querySelector('.table-selectable-check');
        if (checkbox) checkbox.checked = event.target.checked;
      });
      updateBulkState();
    });

    rows.forEach((row) => {
      row.querySelector('.table-selectable-check')?.addEventListener('change', updateBulkState);
    });

    root.querySelectorAll('[data-table-export]').forEach((button) => {
      button.addEventListener('click', async () => {
        if (button.dataset.tableExport === 'excel') {
          downloadExcel(filtered);
          return;
        }
        const csv = csvFor(filtered);
        if (button.dataset.tableExport === 'copy') {
          await navigator.clipboard?.writeText(csv);
          return;
        }
        downloadCsv(csv);
      });
    });

    root.querySelectorAll('[data-table-bulk-action]').forEach((button) => {
      button.addEventListener('click', () => {
        if (button.dataset.tableBulkAction === 'clear_selection') {
          rows.forEach((row) => {
            const checkbox = row.querySelector('.table-selectable-check');
            if (checkbox) checkbox.checked = false;
          });
          updateBulkState();
          return;
        }
        if (button.dataset.tableBulkAction === 'export_selected') {
          const selected = selectedRows();
          downloadCsv(csvFor(selected.length ? selected : filtered));
        }
      });
    });

    const importFile = root.querySelector('[data-table-import-file]');
    root.querySelector('[data-table-import-trigger]')?.addEventListener('click', () => importFile?.click());
    importFile?.addEventListener('change', () => {
      if (importFile.files.length) {
        root.querySelector('[data-table-import-form]')?.submit();
      }
    });

    rows.forEach((row) => {
      row.addEventListener('click', (event) => {
        if (event.target.closest('a,button,input,select,textarea,.dropdown-menu,label')) return;
        if (row.dataset.href) window.location.href = row.dataset.href;
      });
    });

    document.addEventListener('keydown', (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && search) {
        event.preventDefault();
        search.focus();
      }
    });

    applyInitialState();
    render();
  });

  document.querySelectorAll('[data-template-preview-source]').forEach((textarea) => {
    const target = document.querySelector(textarea.dataset.templatePreviewSource);
    if (!target) return;
    const render = () => {
      target.srcdoc = textarea.value;
    };
    textarea.addEventListener('input', render);
    render();
  });

  document.querySelectorAll('.table-row-link[data-href]').forEach((row) => {
    if (row.closest('[data-enhanced-table]')) return;
    row.addEventListener('click', (event) => {
      if (event.target.closest('a,button,input,select,textarea,.dropdown-menu')) return;
      window.location.href = row.dataset.href;
    });
  });

  document.querySelectorAll('[data-global-search]').forEach((root) => {
    const input = root.querySelector('[data-global-search-input]');
    const results = root.querySelector('[data-global-search-results]');
    const url = root.dataset.searchUrl;
    let controller = null;
    const render = (items, query) => {
      if (!results) return;
      if (!query) {
        results.innerHTML = '<div class="dropdown-item text-secondary">Start typing to search.</div>';
        root.classList.remove('show');
        return;
      }
      root.classList.add('show');
      if (!items.length) {
        results.innerHTML = '<div class="dropdown-item text-secondary">No matching authorised records.</div>';
        return;
      }
      results.innerHTML = items.map((item) => `
        <a class="dropdown-item" href="${item.href}">
          <span class="avatar avatar-sm bg-primary-lt me-2"><i class="ti ${item.icon || 'ti-search'}"></i></span>
          <span>
            <span class="d-block">${item.title}</span>
            <small class="text-secondary">${item.meta}</small>
          </span>
        </a>
      `).join('');
    };
    input?.addEventListener('input', async () => {
      const query = input.value.trim();
      if (controller) controller.abort();
      if (query.length < 2) {
        render([], query);
        return;
      }
      controller = new AbortController();
      try {
        const response = await fetch(`${url}?q=${encodeURIComponent(query)}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
        const payload = await response.json();
        render(payload.results || [], query);
      } catch (error) {
        if (error.name !== 'AbortError') render([], query);
      }
    });
    document.addEventListener('click', (event) => {
      if (!root.contains(event.target)) root.classList.remove('show');
    });
  });
});
