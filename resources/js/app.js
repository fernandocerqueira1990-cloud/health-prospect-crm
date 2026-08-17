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

const initPipelineKanban = () => {
    const board = document.querySelector('#pipeline-kanban');

    if (!board) {
        return;
    }

    const csrfToken = board.dataset.csrfToken;

    const cards = board.querySelectorAll('[data-kanban-card]');
    const stages = board.querySelectorAll('[data-kanban-stage]');

    const lossModal = document.querySelector('#loss-reason-modal');
    const lossReason = document.querySelector('#kanban-loss-reason');
    const lossNotes = document.querySelector('#kanban-loss-notes');
    const lossError = document.querySelector('#kanban-loss-error');
    const lossCancel = document.querySelector('#kanban-loss-cancel');
    const lossConfirm = document.querySelector('#kanban-loss-confirm');

    let draggedCard = null;
    let pendingLostStageId = null;

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
