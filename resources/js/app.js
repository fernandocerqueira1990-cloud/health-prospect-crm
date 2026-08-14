const body = document.body;
const openButton = document.querySelector('[data-sidebar-toggle]');
const closeButton = document.querySelector('[data-sidebar-close]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');

const openSidebar = () => {
    body.classList.add('sidebar-open');
    if (window.innerWidth < 1024) {
        body.classList.add('overflow-hidden');
    }
};

const closeSidebar = () => {
    body.classList.remove('sidebar-open', 'overflow-hidden');
};

openButton?.addEventListener('click', openSidebar);
closeButton?.addEventListener('click', closeSidebar);
backdrop?.addEventListener('click', closeSidebar);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSidebar();
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});
