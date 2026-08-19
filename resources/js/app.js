const body = document.body;
const openButton = document.querySelector('[data-sidebar-toggle]');
const closeButton = document.querySelector('[data-sidebar-close]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');
const collapseButton = document.querySelector('[data-sidebar-collapse]');
const collapseIcon = document.querySelector('[data-sidebar-collapse-icon]');
const sidebarStorageKey = 'crm-sidebar-collapsed';
const pipelineFilterOpen = document.querySelector('[data-pipeline-filter-open]');
const pipelineFilterDrawer = document.querySelector(
    '[data-pipeline-filter-drawer]',
);
const pipelineFilterBackdrop = document.querySelector(
    '[data-pipeline-filter-backdrop]',
);
const pipelineFilterClose = document.querySelectorAll(
    '[data-pipeline-filter-close]',
);

const syncBodyScroll = () => {
    const mobileSidebarOpen = body.classList.contains('sidebar-open')
        && window.innerWidth < 1024;
    const filterDrawerOpen = body.classList.contains('pipeline-filter-open');

    body.classList.toggle(
        'overflow-hidden',
        mobileSidebarOpen || filterDrawerOpen,
    );
};

const syncSidebarCollapseButton = () => {
    const collapsed = document.documentElement.classList.contains(
        'sidebar-collapsed',
    );

    collapseButton?.setAttribute('aria-expanded', String(!collapsed));
    collapseButton?.setAttribute(
        'aria-label',
        collapsed ? 'Expandir menu' : 'Recolher menu',
    );
    collapseButton?.setAttribute(
        'title',
        collapsed ? 'Expandir menu' : 'Recolher menu',
    );

    if (collapseIcon) {
        collapseIcon.textContent = collapsed ? '›' : '‹';
    }
};

const openSidebar = () => {
    body.classList.add('sidebar-open');
    syncBodyScroll();
};

const closeSidebar = () => {
    body.classList.remove('sidebar-open');
    syncBodyScroll();
};

const openPipelineFilters = () => {
    body.classList.add('pipeline-filter-open');
    pipelineFilterOpen?.setAttribute('aria-expanded', 'true');
    pipelineFilterDrawer?.setAttribute('aria-hidden', 'false');
    if (pipelineFilterDrawer) {
        pipelineFilterDrawer.inert = false;
    }
    syncBodyScroll();
    pipelineFilterDrawer?.querySelector('button')?.focus();
};

const closePipelineFilters = () => {
    body.classList.remove('pipeline-filter-open');
    pipelineFilterOpen?.setAttribute('aria-expanded', 'false');
    pipelineFilterDrawer?.setAttribute('aria-hidden', 'true');
    if (pipelineFilterDrawer) {
        pipelineFilterDrawer.inert = true;
    }
    syncBodyScroll();
    pipelineFilterOpen?.focus();
};

openButton?.addEventListener('click', openSidebar);
closeButton?.addEventListener('click', closeSidebar);
backdrop?.addEventListener('click', closeSidebar);
pipelineFilterOpen?.addEventListener('click', openPipelineFilters);
pipelineFilterBackdrop?.addEventListener('click', closePipelineFilters);
pipelineFilterClose.forEach((button) => {
    button.addEventListener('click', closePipelineFilters);
});
collapseButton?.addEventListener('click', () => {
    const collapsed = document.documentElement.classList.toggle(
        'sidebar-collapsed',
    );

    try {
        localStorage.setItem(sidebarStorageKey, String(collapsed));
    } catch (error) {
        // A preferência permanece válida durante a navegação atual.
    }

    syncSidebarCollapseButton();
});

syncSidebarCollapseButton();

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSidebar();
        closePipelineFilters();
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});

const initPipelineKanban = () => {
    const board = document.querySelector('#pipeline-kanban');

    if (!board) {
        return;
    }

    const csrfToken = board.dataset.csrfToken;

    const cards = board.querySelectorAll('[data-kanban-card]');
    const stages = board.querySelectorAll('[data-kanban-stage]');
    const stageVisibilityControls = document.querySelectorAll(
        '[data-stage-visibility]',
    );

    const lossModal = document.querySelector('#loss-reason-modal');
    const lossReason = document.querySelector('#kanban-loss-reason');
    const lossNotes = document.querySelector('#kanban-loss-notes');
    const lossError = document.querySelector('#kanban-loss-error');
    const lossCancel = document.querySelector('#kanban-loss-cancel');
    const lossConfirm = document.querySelector('#kanban-loss-confirm');

    let draggedCard = null;
    let pendingLostStageId = null;

    stageVisibilityControls.forEach((control) => {
        control.addEventListener('change', () => {
            const stage = board.querySelector(
                `[data-kanban-stage][data-stage-id="${control.value}"]`,
            );

            stage?.classList.toggle('hidden', !control.checked);
        });
    });

    const clearStageHighlight = () => {
        stages.forEach((stage) => {
            stage.classList.remove(
                'ring-2',
                'ring-teal-400',
                'ring-offset-2',
            );
        });
    };

    const setCardBusy = (card, busy) => {
        card.classList.toggle('opacity-50', busy);
        card.classList.toggle('pointer-events-none', busy);
    };

    const moveOpportunity = async (
        card,
        stageId,
        lossReasonId = null,
        notes = null,
    ) => {
        setCardBusy(card, true);

        try {
            const response = await fetch(card.dataset.moveUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    stage_id: Number(stageId),
                    loss_reason_id: lossReasonId
                        ? Number(lossReasonId)
                        : null,
                    notes: notes || null,
                }),
            });

            if (!response.ok) {
                let message = 'Não foi possível mover a oportunidade.';

                try {
                    const data = await response.json();

                    if (data.message) {
                        message = data.message;
                    }

                    if (data.errors) {
                        const firstError = Object.values(data.errors)
                            .flat()
                            .at(0);

                        if (firstError) {
                            message = firstError;
                        }
                    }
                } catch {
                    // Mantém mensagem padrão.
                }

                throw new Error(message);
            }

            window.location.reload();
        } catch (error) {
            setCardBusy(card, false);

            window.alert(
                error instanceof Error
                    ? error.message
                    : 'Erro ao mover oportunidade.',
            );
        }
    };

    const closeLossModal = () => {
        lossModal?.classList.add('hidden');
        lossModal?.classList.remove('flex');
        lossModal?.setAttribute('aria-hidden', 'true');

        if (lossReason) {
            lossReason.value = '';
        }

        if (lossNotes) {
            lossNotes.value = '';
        }

        lossError?.classList.add('hidden');

        pendingLostStageId = null;
        draggedCard = null;
    };

    const openLossModal = (stageId) => {
        pendingLostStageId = stageId;

        lossModal?.classList.remove('hidden');
        lossModal?.classList.add('flex');
        lossModal?.setAttribute('aria-hidden', 'false');

        lossReason?.focus();
    };

    cards.forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            draggedCard = card;

            card.classList.add('opacity-50');

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData(
                'text/plain',
                card.dataset.opportunityId,
            );
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('opacity-50');
            clearStageHighlight();

            if (pendingLostStageId === null) {
                draggedCard = null;
            }
        });
    });

    stages.forEach((stage) => {
        stage.addEventListener('dragover', (event) => {
            event.preventDefault();

            if (!draggedCard) {
                return;
            }

            event.dataTransfer.dropEffect = 'move';

            clearStageHighlight();

            stage.classList.add(
                'ring-2',
                'ring-teal-400',
                'ring-offset-2',
            );
        });

        stage.addEventListener('dragleave', (event) => {
            if (!stage.contains(event.relatedTarget)) {
                stage.classList.remove(
                    'ring-2',
                    'ring-teal-400',
                    'ring-offset-2',
                );
            }
        });

        stage.addEventListener('drop', async (event) => {
            event.preventDefault();

            clearStageHighlight();

            if (!draggedCard) {
                return;
            }

            const targetStageId = stage.dataset.stageId;
            const targetStageType = stage.dataset.stageType;
            const currentStageId = draggedCard.dataset.currentStageId;

            if (targetStageId === currentStageId) {
                draggedCard = null;

                return;
            }

            if (targetStageType === 'lost') {
                openLossModal(targetStageId);

                return;
            }

            await moveOpportunity(
                draggedCard,
                targetStageId,
            );

            draggedCard = null;
        });
    });

    lossCancel?.addEventListener('click', closeLossModal);

    lossModal?.addEventListener('click', (event) => {
        if (event.target === lossModal) {
            closeLossModal();
        }
    });

    lossConfirm?.addEventListener('click', async () => {
        if (!draggedCard || !pendingLostStageId) {
            closeLossModal();

            return;
        }

        if (!lossReason?.value) {
            lossError?.classList.remove('hidden');

            return;
        }

        lossError?.classList.add('hidden');

        const card = draggedCard;
        const stageId = pendingLostStageId;
        const reasonId = lossReason.value;
        const notes = lossNotes?.value ?? '';

        lossModal?.classList.add('hidden');
        lossModal?.classList.remove('flex');

        await moveOpportunity(
            card,
            stageId,
            reasonId,
            notes,
        );
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape'
            && lossModal
            && !lossModal.classList.contains('hidden')
        ) {
            closeLossModal();
        }
    });
};

document.addEventListener('DOMContentLoaded', initPipelineKanban);

/* Techsallus CRM — preserve sidebar scroll position */
const initSidebarScrollPersistence = () => {
    const sidebarNav = document.querySelector('[data-sidebar-nav]');

    if (!sidebarNav) {
        return;
    }

    const storageKey = 'techsallus-crm-sidebar-scroll-top';

    try {
        const storedPosition = sessionStorage.getItem(storageKey);

        if (storedPosition !== null) {
            sidebarNav.scrollTop = Number(storedPosition);
        }
    } catch {
        // Mantém o comportamento normal caso sessionStorage não esteja disponível.
    }

    const saveScrollPosition = () => {
        try {
            sessionStorage.setItem(
                storageKey,
                String(sidebarNav.scrollTop),
            );
        } catch {
            // A navegação continua funcionando normalmente.
        }
    };

    sidebarNav.addEventListener('scroll', saveScrollPosition, {
        passive: true,
    });

    sidebarNav.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', saveScrollPosition);
    });

    window.addEventListener('pagehide', saveScrollPosition);
};

document.addEventListener(
    'DOMContentLoaded',
    initSidebarScrollPersistence,
);
