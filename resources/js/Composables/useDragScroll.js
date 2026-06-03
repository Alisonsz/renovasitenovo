import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Draggable, seamless-infinite horizontal carousel — kept intentionally simple.
 *
 * - Free scrolling: drag with the mouse, swipe with the finger (native), stop
 *   wherever you want. No scroll-snap fighting the gesture.
 * - Seamless infinite loop: items are rendered in N identical sets; while idle
 *   we silently jump by whole sets to keep the viewport in the middle (the
 *   pixels are identical, so it's invisible).
 * - Autoplay with "center first, then resume": after you interact, when the
 *   autoplay is about to come back it FIRST animates the nearest card to center,
 *   then continues the auto-scroll from there.
 *
 * Layout-safe: it changes no markup/classes.
 *
 * @param {import('vue').Ref<HTMLElement|null>} trackRef
 * @param {{ enabled?: () => boolean, autoplayMs?: number, autoplayResumeMs?: number, sets?: number }} options
 */
export function useDragScroll(trackRef, options = {}) {
    const opts = {
        enabled: () => true,
        autoplayMs: 0,
        autoplayResumeMs: 6000,
        sets: 3,
        ...options,
    };

    let mouseDown = false;
    let touching = false;
    let lastX = 0;
    let moved = false;
    let oneSet = 0;
    let autoTimer = null;
    let resumeTimer = null;
    let settleTimer = null;
    let ro = null;

    // Quick re-center delay after a manual scroll settles (mobile only).
    const SETTLE_CENTER_MS = 700;

    const isCoarsePointer = () =>
        typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(pointer: coarse)').matches;

    const isEnabled = () => {
        try {
            return !!opts.enabled();
        } catch {
            return false;
        }
    };

    function setBehavior(el, mode) {
        el.style.scrollBehavior = mode; // '' restores the CSS scroll-smooth
    }

    function items() {
        const el = trackRef.value;
        return el ? [...el.querySelectorAll('[data-carousel-item]')] : [];
    }

    function measure() {
        const el = trackRef.value;
        if (!el) return;
        const list = items();
        if (list.length >= opts.sets && list.length % opts.sets === 0) {
            const perSet = list.length / opts.sets;
            const delta = list[perSet].offsetLeft - list[0].offsetLeft;
            if (delta > 0) {
                oneSet = delta;
                return;
            }
        }
        const ul = el.querySelector('ul') || el.firstElementChild;
        oneSet = (ul ? ul.scrollWidth : el.scrollWidth) / opts.sets;
    }

    function loopable(el) {
        return isEnabled() && oneSet > 0 && oneSet >= el.clientWidth - 1;
    }

    function homeOffset() {
        return Math.floor(opts.sets / 2) * oneSet;
    }

    function recenter() {
        const el = trackRef.value;
        if (!el) return;
        measure();
        if (!loopable(el)) return;
        // Start on the middle set, then snap the nearest card to the center so the
        // first frame is properly centered regardless of the track's padding.
        setBehavior(el, 'auto');
        el.scrollLeft = homeOffset();
        // Only snap-to-center when a single card fills the viewport (mobile).
        // When several cards are visible (desktop), centering one card would crop
        // the side cards — keep the set edge-aligned instead.
        if (singlePerView()) {
            const t = centerTarget();
            if (t !== null) el.scrollLeft = t;
        }
        setBehavior(el, '');
    }

    /** True when roughly one card spans the whole viewport (mobile carousels). */
    function singlePerView() {
        const el = trackRef.value;
        const first = items()[0];
        if (!el || !first) return false;
        return first.getBoundingClientRect().width >= el.clientWidth * 0.8;
    }

    /**
     * Scroll position that puts the card nearest the viewport center exactly in
     * the center. Returns null if there's nothing to do.
     */
    function centerTarget() {
        const el = trackRef.value;
        if (!el) return null;
        const list = items();
        if (!list.length) return null;
        const viewCenter = el.scrollLeft + el.clientWidth / 2;
        let best = null;
        let bestDist = Infinity;
        for (const it of list) {
            const c = it.offsetLeft + it.offsetWidth / 2;
            const d = Math.abs(c - viewCenter);
            if (d < bestDist) {
                bestDist = d;
                best = it;
            }
        }
        if (!best) return null;
        return Math.round(best.offsetLeft + best.offsetWidth / 2 - el.clientWidth / 2);
    }

    /** Silent loop reposition: jump whole sets to keep near the middle. */
    function wrap() {
        const el = trackRef.value;
        if (!el || !loopable(el) || oneSet <= 0) return;
        const drift = el.scrollLeft - homeOffset();
        if (Math.abs(drift) < oneSet * 0.5) return;
        const jumps = Math.round(drift / oneSet);
        if (jumps === 0) return;
        setBehavior(el, 'auto');
        el.scrollLeft -= jumps * oneSet;
        setBehavior(el, '');
    }

    /** Width of one card including the gap. */
    function step() {
        const el = trackRef.value;
        const first = items()[0];
        if (!el || !first) return el ? el.clientWidth : 0;
        const styles = window.getComputedStyle(first.parentElement || el);
        const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;
        return first.getBoundingClientRect().width + gap;
    }

    /**
     * Smoothly scroll so the card nearest the viewport center becomes centered.
     * Returns the time (ms) the animation roughly needs.
     */
    function centerNearest() {
        const el = trackRef.value;
        if (!el) return 0;
        // Centering only makes sense for single-card-per-view (mobile). With many
        // cards visible, snapping one to center crops the side cards.
        if (!singlePerView()) return 0;
        const target = centerTarget();
        if (target === null || Math.abs(target - el.scrollLeft) < 2) return 0;
        setBehavior(el, ''); // use CSS smooth
        el.scrollTo({ left: target, behavior: 'smooth' });
        return 380; // approx smooth-scroll duration
    }

    // ---- autoplay ----
    function tickOnce() {
        const el = trackRef.value;
        if (!el || mouseDown || el.scrollWidth <= el.clientWidth) return;
        if (loopable(el)) {
            wrap();
            el.scrollBy({ left: step(), behavior: 'smooth' });
        } else {
            const max = el.scrollWidth - el.clientWidth;
            const next = el.scrollLeft + step();
            el.scrollTo({ left: next >= max - 4 ? 0 : next, behavior: 'smooth' });
        }
    }

    function startAuto() {
        if (!opts.autoplayMs) return;
        stopAuto();
        autoTimer = setInterval(tickOnce, opts.autoplayMs);
    }

    function stopAuto() {
        if (autoTimer) clearInterval(autoTimer);
        autoTimer = null;
    }

    /**
     * Pause autoplay during/after interaction. When it resumes, FIRST center the
     * nearest card, then begin auto-scrolling — exactly the requested behavior.
     */
    function pauseAuto() {
        stopAuto();
        if (resumeTimer) clearTimeout(resumeTimer);
        if (!opts.autoplayMs) return;
        resumeTimer = setTimeout(() => {
            if (mouseDown) {
                pauseAuto();
                return;
            }
            wrap();
            const settle = centerNearest();
            // After centering finishes, resume the regular auto-scroll.
            resumeTimer = setTimeout(startAuto, settle + 60);
        }, opts.autoplayResumeMs);
    }

    // ---- mouse drag (pointer events; touch uses native scrolling) ----
    function onPointerDown(e) {
        if (e.pointerType !== 'mouse') return;
        const el = trackRef.value;
        if (!el || e.button !== 0) return;
        mouseDown = true;
        moved = false;
        lastX = e.clientX;
        setBehavior(el, 'auto');
        pauseAuto();
    }

    function onPointerMove(e) {
        if (!mouseDown) return;
        const el = trackRef.value;
        if (!el) return;
        const dx = e.clientX - lastX;
        lastX = e.clientX;
        if (Math.abs(dx) > 2) moved = true;
        el.scrollLeft -= dx;
        wrap();
    }

    function onPointerUp() {
        if (!mouseDown) return;
        const el = trackRef.value;
        if (el) setBehavior(el, '');
        mouseDown = false;
        pauseAuto(); // schedule "center then resume"
    }

    function onClickCapture(e) {
        if (moved) {
            e.stopPropagation();
            e.preventDefault();
            moved = false;
        }
    }

    /**
     * Touch/native scroll: keep the loop seamless, and on mobile re-center the
     * nearest card shortly (0.7s) after the scroll settles — so a manual spin
     * that stops "crooked" snaps to center quickly, without waiting for autoplay.
     */
    function onScroll() {
        if (mouseDown) return;
        if (settleTimer) clearTimeout(settleTimer);
        settleTimer = setTimeout(() => {
            wrap();
            // Only auto-center on touch devices; desktop centering is handled by
            // the autoplay-resume flow and the mouse-drag release.
            if (!touching && isCoarsePointer()) {
                centerNearest();
            }
        }, SETTLE_CENTER_MS);
    }

    function onTouchStart() {
        touching = true;
        pauseAuto();
        if (settleTimer) clearTimeout(settleTimer);
    }

    function onTouchEnd() {
        touching = false;
        // Re-arm the settle timer so the last fling is centered after it rests.
        if (settleTimer) clearTimeout(settleTimer);
        settleTimer = setTimeout(() => {
            wrap();
            if (isCoarsePointer()) centerNearest();
        }, SETTLE_CENTER_MS);
    }

    function attach() {
        const el = trackRef.value;
        if (!el) return;
        el.addEventListener('pointerdown', onPointerDown);
        window.addEventListener('pointermove', onPointerMove, { passive: true });
        window.addEventListener('pointerup', onPointerUp, { passive: true });
        el.addEventListener('click', onClickCapture, true);
        el.addEventListener('scroll', onScroll, { passive: true });
        el.addEventListener('wheel', pauseAuto, { passive: true });
        el.addEventListener('touchstart', onTouchStart, { passive: true });
        el.addEventListener('touchend', onTouchEnd, { passive: true });
        el.addEventListener('touchcancel', onTouchEnd, { passive: true });
        el.style.webkitOverflowScrolling = 'touch';
        if (window.ResizeObserver) {
            ro = new ResizeObserver(measure);
            ro.observe(el);
        }
    }

    function detach() {
        const el = trackRef.value;
        if (el) {
            el.removeEventListener('pointerdown', onPointerDown);
            el.removeEventListener('click', onClickCapture, true);
            el.removeEventListener('scroll', onScroll);
            el.removeEventListener('wheel', pauseAuto);
            el.removeEventListener('touchstart', onTouchStart);
            el.removeEventListener('touchend', onTouchEnd);
            el.removeEventListener('touchcancel', onTouchEnd);
        }
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        if (ro) {
            ro.disconnect();
            ro = null;
        }
        stopAuto();
        if (resumeTimer) clearTimeout(resumeTimer);
        if (settleTimer) clearTimeout(settleTimer);
    }

    onMounted(() => {
        attach();
        requestAnimationFrame(() => {
            recenter();
            startAuto();
        });
    });

    onBeforeUnmount(detach);

    return { recenter, measure, pauseAuto, startAuto };
}
