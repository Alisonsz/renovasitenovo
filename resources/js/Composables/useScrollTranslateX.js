import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

export function useScrollTranslateX(target, { base = 0, speed = 0.5, direction = 1, max = 90 } = {}) {
    const offset = ref(0);
    const active = ref(false);
    const reducedMotion = typeof window !== 'undefined'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;
    let frame = null;
    let observer = null;

    const style = computed(() => ({
        transform: `translate3d(${base + offset.value}px, 0, 0)`,
        willChange: active.value ? 'transform' : 'auto',
    }));

    function measure() {
        frame = null;

        if (!target.value || reducedMotion?.matches) {
            offset.value = 0;
            return;
        }

        const rect = target.value.getBoundingClientRect();
        const viewport = window.innerHeight || document.documentElement.clientHeight;
        const progress = ((viewport - rect.top) / (viewport + rect.height)) - 0.5;
        const next = Math.max(-max, Math.min(max, progress * max * speed * 2 * direction));
        offset.value = Number(next.toFixed(2));
    }

    function requestMeasure() {
        if (!active.value || frame !== null) return;
        frame = window.requestAnimationFrame(measure);
    }

    onMounted(() => {
        if (!target.value || reducedMotion?.matches) return;

        observer = new IntersectionObserver(([entry]) => {
            active.value = entry.isIntersecting;
            if (active.value) requestMeasure();
        }, { rootMargin: '20% 0px' });

        observer.observe(target.value);
        window.addEventListener('scroll', requestMeasure, { passive: true });
        window.addEventListener('resize', requestMeasure);
        requestMeasure();
    });

    onBeforeUnmount(() => {
        if (frame !== null) window.cancelAnimationFrame(frame);
        observer?.disconnect();
        window.removeEventListener('scroll', requestMeasure);
        window.removeEventListener('resize', requestMeasure);
    });

    return { style };
}
