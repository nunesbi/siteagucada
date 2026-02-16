

(function () {
  const PANEL_ID = 'mobile-menu-panel';

  function buildPanel(header) {
    let panel = document.getElementById(PANEL_ID);
    if (panel) return panel;

    panel = document.createElement('div');
    panel.id = PANEL_ID;
    panel.className =
      'sm:hidden absolute top-full left-0 right-0 bg-white/95 backdrop-blur-sm border-b border-border shadow-lg hidden';

    const container = document.createElement('div');
    container.className = 'container';

    const inner = document.createElement('div');
    inner.className = 'flex flex-col gap-4 py-4';


    const desktopNav = header.querySelector('nav.hidden.sm\\:flex');
    if (desktopNav) {
      desktopNav.querySelectorAll('a').forEach((a) => {
        const clone = a.cloneNode(true);
        clone.className = 'block';
        const span = clone.querySelector('span');
        if (span) {
          span.className =
            'text-base font-medium text-foreground hover:text-primary transition-colors duration-200';
        }
        inner.appendChild(clone);
      });
    }


    const desktopActions = header.querySelector('div.hidden.sm\\:flex');
    if (desktopActions) {
      const actionsWrap = document.createElement('div');
      actionsWrap.className = 'flex flex-wrap gap-3 pt-2';
      desktopActions
        .querySelectorAll('a')
        .forEach((a) => actionsWrap.appendChild(a.cloneNode(true)));
      inner.appendChild(actionsWrap);
    }

    container.appendChild(inner);
    panel.appendChild(container);
    header.appendChild(panel);
    return panel;
  }

  function wire(toggleBtn, header) {
    if (!toggleBtn || !header) return;
    if (toggleBtn.dataset.mobileMenuWired === '1') return;
    toggleBtn.dataset.mobileMenuWired = '1';

    const panel = buildPanel(header);

    const closePanel = () => {
      panel.classList.add('hidden');
      toggleBtn.setAttribute('aria-expanded', 'false');
    };

    const openPanel = () => {
      panel.classList.remove('hidden');
      toggleBtn.setAttribute('aria-expanded', 'true');
    };

    toggleBtn.setAttribute('aria-expanded', 'false');

    toggleBtn.addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (panel.classList.contains('hidden')) openPanel();
        else closePanel();
      },
      { passive: false }
    );


    panel.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      if (link) closePanel();
    });


    document.addEventListener('click', (e) => {
      if (panel.classList.contains('hidden')) return;
      if (toggleBtn.contains(e.target)) return;
      if (panel.contains(e.target)) return;
      closePanel();
    });


    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closePanel();
    });
  }

  function init() {
    const header = document.querySelector('header');
    const toggleBtn = document.querySelector('button[aria-label="Toggle menu"]');
    if (!header || !toggleBtn) return;
    wire(toggleBtn, header);
  }


  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }


  const obs = new MutationObserver(() => init());
  obs.observe(document.documentElement, { childList: true, subtree: true });
})();
