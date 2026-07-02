/**
 * NeivActiva Dashboard Logic — Premium Edition
 */

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    animateNumbers();
    animateBentoCards();
    initHeroSlider();
    initEventsCarousel();
    initEventEnrollment();

    // Search
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.event-card-modern');
            cards.forEach(card => {
                const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
                card.style.display = title.includes(term) ? '' : 'none';
            });
        });
    }
});

function initHeroSlider() {
    const slider = document.querySelector('[data-hero-slider]');
    if (!slider) {
        initSmoothEventScroll();
        return;
    }

    const slides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
    const dotsContainer = slider.querySelector('[data-hero-dots]');
    if (!slides.length) {
        initSmoothEventScroll();
        return;
    }

    let active = 0;
    let autoplayId = null;
    let resumeTimer = null;
    const interval = 5600;

    const goTo = (index) => {
        active = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === active;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
        dotsContainer?.querySelectorAll('.hero-slider-dot').forEach((dot, dotIndex) => {
            dot.classList.toggle('active', dotIndex === active);
        });
    };

    const stopAutoplay = () => {
        if (autoplayId) {
            window.clearInterval(autoplayId);
            autoplayId = null;
        }
    };

    const startAutoplay = () => {
        if (autoplayId || slides.length <= 1) return;
        autoplayId = window.setInterval(() => {
            if (!document.hidden) goTo(active + 1);
        }, interval);
    };

    const pauseAndResume = () => {
        stopAutoplay();
        if (resumeTimer) window.clearTimeout(resumeTimer);
        resumeTimer = window.setTimeout(startAutoplay, 7000);
    };

    if (dotsContainer) {
        dotsContainer.innerHTML = '';
        slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'hero-slider-dot';
            dot.setAttribute('aria-label', `Mostrar evento ${index + 1}`);
            dot.addEventListener('click', () => {
                pauseAndResume();
                goTo(index);
            });
            dotsContainer.appendChild(dot);
        });
    }

    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopAutoplay();
        else startAutoplay();
    });

    goTo(0);
    startAutoplay();
    initSmoothEventScroll();
}

function initSmoothEventScroll() {
    document.querySelectorAll('[data-scroll-events]').forEach(button => {
        button.addEventListener('click', event => {
            const target = document.getElementById('eventos-listados-dashboard');
            if (!target) return;
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

function initCharts() {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    const context = ctx.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(255, 184, 0, 0.35)');
    gradient.addColorStop(0.5, 'rgba(255, 184, 0, 0.08)');
    gradient.addColorStop(1, 'rgba(255, 184, 0, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Asistencia',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: '#FFB800',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.45,
                pointRadius: 5,
                pointBackgroundColor: '#FFB800',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 3,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#2D1002',
                pointHoverBorderColor: '#FFB800',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2D1002',
                    titleColor: '#FFB800',
                    bodyColor: '#FFFFFF',
                    padding: 14,
                    cornerRadius: 12,
                    displayColors: false,
                    titleFont: { size: 11, weight: '700' },
                    bodyFont: { size: 14, weight: '800' },
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.y.toLocaleString() + ' asistentes';
                        }
                    }
                }
            },
            scales: {
                y: {
                    display: true,
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: {
                        color: '#9CA3AF',
                        font: { size: 10, weight: '600' },
                        padding: 8,
                        callback: function(v) { return v >= 1000 ? (v/1000)+'K' : v; }
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#9CA3AF',
                        font: { size: 11, weight: '600' },
                        padding: 8
                    },
                    border: { display: false }
                }
            }
        }
    });
}

function animateNumbers() {
    const stats = document.querySelectorAll('.stat-big-number');
    if (!stats.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.textContent.replace(/[^0-9]/g, ''));
                if (isNaN(target) || target === 0) return;

                let count = 0;
                const duration = 1800;
                const start = performance.now();

                const update = (now) => {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease-out cubic
                    const ease = 1 - Math.pow(1 - progress, 3);
                    count = Math.floor(ease * target);
                    el.textContent = count.toLocaleString();
                    if (progress < 1) {
                        requestAnimationFrame(update);
                    } else {
                        el.textContent = target.toLocaleString();
                    }
                };
                requestAnimationFrame(update);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.3 });

    stats.forEach(s => observer.observe(s));
}

function animateBentoCards() {
    const cards = document.querySelectorAll('.bento-item');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.6s cubic-bezier(0.34,1.56,0.64,1), transform 0.6s cubic-bezier(0.34,1.56,0.64,1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + i * 80);
    });
}

function initEventsCarousel() {
    const carousel = document.querySelector('[data-events-carousel]');
    if (!carousel) return;

    const track = carousel.querySelector('[data-carousel-track]');
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    const dotsContainer = carousel.querySelector('[data-carousel-dots]');
    const cards = track ? Array.from(track.querySelectorAll('.event-card-modern')) : [];
    if (!track || !cards.length) {
        prev?.setAttribute('disabled', 'disabled');
        next?.setAttribute('disabled', 'disabled');
        return;
    }

    let isDragging = false;
    let startX = 0;
    let startScrollLeft = 0;
    let autoplayId = null;
    let resumeTimer = null;
    let pages = [];
    let ticking = false;

    const getStep = () => {
        const firstCard = cards[0];
        const styles = window.getComputedStyle(track);
        const gap = parseFloat(styles.columnGap || styles.gap || 16);
        return firstCard.getBoundingClientRect().width + gap;
    };

    const getVisibleCount = () => Math.max(1, Math.floor((track.clientWidth + 1) / getStep()));

    const getMaxScroll = () => Math.max(0, track.scrollWidth - track.clientWidth);

    const normalizePosition = value => {
        const maxScroll = getMaxScroll();
        return Math.max(0, Math.min(Math.round(value), Math.round(maxScroll)));
    };

    const getCardPosition = card => {
        const cardRect = card.getBoundingClientRect();
        const trackRect = track.getBoundingClientRect();
        return normalizePosition(cardRect.left - trackRect.left + track.scrollLeft);
    };

    const getCardPositions = () => cards.map(getCardPosition);

    const uniquePositions = positions => {
        const clean = [];
        positions.map(normalizePosition).sort((a, b) => a - b).forEach(position => {
            const previous = clean[clean.length - 1];
            if (typeof previous === 'undefined' || Math.abs(previous - position) > 2) {
                clean.push(position);
            }
        });
        return clean;
    };

    const buildPages = () => {
        const visible = getVisibleCount();
        const maxScroll = getMaxScroll();
        const cardPositions = getCardPositions();
        const candidatePages = [0];
        const maxStartIndex = Math.max(0, cards.length - visible);

        for (let i = visible; i <= maxStartIndex; i += visible) {
            candidatePages.push(cardPositions[i] || 0);
        }

        if (maxScroll > 0) {
            candidatePages.push(maxScroll);
        }

        pages = uniquePositions(candidatePages);
        renderDots();
        updateControls();
    };

    const nearestPage = () => {
        if (!pages.length) return 0;
        const left = track.scrollLeft;
        let nearest = 0;
        let distance = Infinity;
        pages.forEach((page, index) => {
            const diff = Math.abs(page - left);
            if (diff < distance) {
                distance = diff;
                nearest = index;
            }
        });
        return nearest;
    };

    const nextPage = () => {
        const left = track.scrollLeft;
        return pages.findIndex(page => page > left + 6);
    };

    const previousPage = () => {
        const left = track.scrollLeft;
        for (let i = pages.length - 1; i >= 0; i--) {
            if (pages[i] < left - 6) return i;
        }
        return -1;
    };

    const goToPage = (index) => {
        const target = Math.max(0, Math.min(index, pages.length - 1));
        track.scrollTo({ left: pages[target] || 0, behavior: 'smooth' });
        window.setTimeout(updateControls, 260);
    };

    const renderDots = () => {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        pages.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'carousel-dot';
            dot.setAttribute('aria-label', `Ir a grupo ${index + 1}`);
            dot.addEventListener('click', () => {
                pauseAutoplay();
                goToPage(index);
            });
            dotsContainer.appendChild(dot);
        });
    };

    const updateControls = () => {
        const maxScroll = getMaxScroll();
        const left = track.scrollLeft;
        const page = nearestPage();

        if (prev) prev.disabled = left <= 3 || maxScroll <= 3;
        if (next) next.disabled = left >= maxScroll - 3 || maxScroll <= 3;

        dotsContainer?.querySelectorAll('.carousel-dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === page);
        });
    };

    const pauseAutoplay = () => {
        if (autoplayId) {
            window.clearInterval(autoplayId);
            autoplayId = null;
        }
        if (resumeTimer) window.clearTimeout(resumeTimer);
        resumeTimer = window.setTimeout(startAutoplay, 7000);
    };

    const startAutoplay = () => {
        if (autoplayId || cards.length <= getVisibleCount()) return;
        autoplayId = window.setInterval(() => {
            if (document.hidden || carousel.matches(':hover')) return;
            const target = nextPage();
            goToPage(target === -1 ? 0 : target);
        }, 5200);
    };

    prev?.addEventListener('click', () => {
        pauseAutoplay();
        const target = previousPage();
        goToPage(target === -1 ? 0 : target);
    });

    next?.addEventListener('click', () => {
        pauseAutoplay();
        const target = nextPage();
        goToPage(target === -1 ? pages.length - 1 : target);
    });

    track.addEventListener('wheel', event => {
        if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;
        event.preventDefault();
        pauseAutoplay();
        track.scrollBy({ left: event.deltaY, behavior: 'smooth' });
    }, { passive: false });

    track.addEventListener('pointerdown', event => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (event.target.closest('button, a, input, form')) return;
        isDragging = true;
        startX = event.clientX;
        startScrollLeft = track.scrollLeft;
        track.classList.add('dragging');
        track.setPointerCapture?.(event.pointerId);
        pauseAutoplay();
    });

    track.addEventListener('pointermove', event => {
        if (!isDragging) return;
        const walk = event.clientX - startX;
        track.scrollLeft = startScrollLeft - walk;
    });

    const endDrag = event => {
        if (!isDragging) return;
        isDragging = false;
        track.classList.remove('dragging');
        track.releasePointerCapture?.(event.pointerId);
        goToPage(nearestPage());
    };

    track.addEventListener('pointerup', endDrag);
    track.addEventListener('pointercancel', endDrag);
    track.addEventListener('mouseleave', event => {
        if (isDragging) endDrag(event);
    });

    track.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(() => {
            updateControls();
            ticking = false;
        });
    }, { passive: true });

    carousel.addEventListener('mouseenter', () => {
        if (autoplayId) {
            window.clearInterval(autoplayId);
            autoplayId = null;
        }
    });
    carousel.addEventListener('mouseleave', startAutoplay);

    window.addEventListener('resize', debounce(() => {
        const page = nearestPage();
        buildPages();
        goToPage(Math.min(page, pages.length - 1));
    }, 180));

    window.requestAnimationFrame(() => {
        buildPages();
        startAutoplay();
    });
}

function debounce(fn, delay) {
    let timer;
    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), delay);
    };
}

function initEventEnrollment() {
    const cards = document.querySelectorAll('[data-event-id]');
    const modal = document.getElementById('eventProfileModal');
    const profileForm = document.getElementById('eventProfileForm');
    let pendingCard = null;

    cards.forEach(card => {
        const button = card.querySelector('[data-action="inscribir-evento"]');
        if (!button) return;

        button.addEventListener('click', () => {
            if (button.disabled || button.classList.contains('is-loading')) return;
            inscribirEvento(card, button);
        });
    });

    document.querySelectorAll('[data-close-profile-modal]').forEach(btn => {
        btn.addEventListener('click', closeProfileModal);
    });

    if (modal) {
        modal.addEventListener('click', event => {
            if (event.target === modal) closeProfileModal();
        });
    }

    if (profileForm) {
        profileForm.addEventListener('submit', event => {
            event.preventDefault();
            if (!pendingCard) return;
            const button = pendingCard.querySelector('[data-action="inscribir-evento"]');
            const formData = new FormData(profileForm);
            closeProfileModal();
            inscribirEvento(pendingCard, button, formData);
        });
    }

    function openProfileModal(card, participante) {
        if (!modal || !profileForm) return;
        pendingCard = card;
        document.getElementById('profileEventoId').value = card.dataset.eventId || '';
        document.getElementById('profileNombre').value = participante?.nombre_completo || document.getElementById('profileNombre').value || '';
        document.getElementById('profileDocumento').value = participante?.documento_identidad || '';
        document.getElementById('profileTelefono').value = participante?.telefono || '';
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => document.getElementById('profileDocumento')?.focus(), 50);
    }

    function closeProfileModal() {
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }

    async function inscribirEvento(card, button, extraData = null) {
        const eventoId = card.dataset.eventId;
        const body = extraData || new FormData();
        body.set('evento_id', eventoId);
        body.set('csrf_token', document.querySelector('#eventProfileForm input[name="csrf_token"]')?.value || '');

        setButtonLoading(button, true);

        try {
            const response = await fetch('?view=inscribir_evento_ajax', {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'fetch' }
            });
            const data = await response.json();

            if (data.ok) {
                markCardAsRegistered(card, button, data);
                showToast({
                    title: 'Inscripcion exitosa',
                    message: 'Tu cupo fue reservado y el QR ya esta listo.',
                    type: 'success',
                    actions: [
                        { label: 'Descargar QR', href: data.descargar_url, primary: true },
                        {
                            label: 'Enviar QR al correo',
                            onClick: () => enviarQrCorreo(data.inscripcion_id, data.token)
                        }
                    ]
                });
                return;
            }

            if (data.code === 'perfil_incompleto') {
                openProfileModal(card, data.participante || {});
                return;
            }

            if (data.code === 'login_required') {
                showToast({
                    title: 'Inicia sesion',
                    message: data.msg || 'Debes iniciar sesion para inscribirte.',
                    type: 'error',
                    actions: [{ label: 'Ir al login', href: '?view=login', primary: true }]
                });
                return;
            }

            if (data.code === 'ya_inscrito') {
                markCardAsRegistered(card, button, data);
            }

            if (data.code === 'sin_cupos') {
                markCardAsFull(button);
            }

            showToast({
                title: data.code === 'ya_inscrito' ? 'Ya estas inscrito' : 'No se pudo inscribir',
                message: data.msg || 'Intentalo nuevamente.',
                type: data.code === 'ya_inscrito' ? 'success' : 'error'
            });
        } catch (error) {
            showToast({
                title: 'Error de conexion',
                message: 'No pudimos completar la accion. Revisa tu conexion e intentalo otra vez.',
                type: 'error'
            });
        } finally {
            setButtonLoading(button, false);
        }
    }
}

function setButtonLoading(button, isLoading) {
    if (!button) return;
    if (isLoading) {
        button.classList.add('is-loading');
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Inscribiendo...</span>';
    } else {
        button.classList.remove('is-loading');
        if (button.dataset.originalHtml && !button.disabled) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }
}

function markCardAsRegistered(card, button, data) {
    const relatedCards = card?.dataset?.eventId
        ? document.querySelectorAll(`[data-event-id="${card.dataset.eventId}"]`)
        : [card].filter(Boolean);

    relatedCards.forEach(relatedCard => {
        relatedCard.querySelectorAll('[data-action="inscribir-evento"]').forEach(relatedButton => {
            relatedButton.disabled = true;
            relatedButton.classList.remove('is-full');
            relatedButton.classList.add('is-registered');
            relatedButton.innerHTML = '<i class="bi bi-check2-circle"></i><span>Ya inscrito</span>';
        });
    });

    if (!card || !data) return;
    if (typeof data.inscritos_actuales !== 'undefined') {
        card.dataset.inscritos = data.inscritos_actuales;
        relatedCards.forEach(relatedCard => {
            relatedCard.dataset.inscritos = data.inscritos_actuales;
            relatedCard.querySelectorAll('.card-inscritos').forEach(el => {
                el.textContent = data.inscritos_actuales;
            });
        });
    }
    if (typeof data.cupos_disponibles !== 'undefined') {
        card.dataset.cupos = data.cupos_disponibles;
        relatedCards.forEach(relatedCard => {
            relatedCard.dataset.cupos = data.cupos_disponibles;
            relatedCard.querySelectorAll('.card-cupos, .card-cupos-inline').forEach(el => {
                el.textContent = data.cupos_disponibles;
            });
        });
    }
}

function markCardAsFull(button) {
    if (!button) return;
    button.disabled = true;
    button.classList.remove('is-registered');
    button.classList.add('is-full');
    button.innerHTML = '<i class="bi bi-x-circle"></i><span>Evento lleno</span>';
}

async function enviarQrCorreo(inscripcionId, token) {
    const body = new FormData();
    body.set('id', inscripcionId);
    body.set('token', token);
    body.set('csrf_token', document.querySelector('#eventProfileForm input[name="csrf_token"]')?.value || '');

    try {
        const response = await fetch('?view=enviar_qr_ajax', {
            method: 'POST',
            body,
            headers: { 'X-Requested-With': 'fetch' }
        });
        const data = await response.json();
        showToast({
            title: data.ok ? 'Correo enviado' : 'Correo no enviado',
            message: data.msg || '',
            type: data.ok ? 'success' : 'error'
        });
    } catch (error) {
        showToast({
            title: 'Correo no enviado',
            message: 'La inscripcion sigue confirmada. Puedes descargar tu QR.',
            type: 'error'
        });
    }
}

function showToast({ title, message, type = 'success', actions = [] }) {
    const container = document.getElementById('dashboardToast');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast-card ${type === 'error' ? 'error' : 'success'}`;

    const titleEl = document.createElement('h4');
    titleEl.textContent = title;
    toast.appendChild(titleEl);

    if (message) {
        const messageEl = document.createElement('p');
        messageEl.textContent = message;
        toast.appendChild(messageEl);
    }

    if (actions.length) {
        const actionsEl = document.createElement('div');
        actionsEl.className = 'toast-actions';
        actions.forEach(action => {
            const el = action.href ? document.createElement('a') : document.createElement('button');
            el.className = `toast-action ${action.primary ? 'primary' : ''}`;
            el.textContent = action.label;
            if (action.href) {
                el.href = action.href;
            }
            if (action.onClick) {
                el.type = 'button';
                el.addEventListener('click', action.onClick);
            }
            actionsEl.appendChild(el);
        });
        toast.appendChild(actionsEl);
    }

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 220);
    }, actions.length ? 9000 : 4500);
}
