const revealTargets = document.querySelectorAll('[data-reveal]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (reducedMotion || !('IntersectionObserver' in window)) {
    revealTargets.forEach((target) => target.classList.add('is-visible'));
} else {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    revealTargets.forEach((target) => {
        if (target.getBoundingClientRect().top < window.innerHeight) {
            target.classList.add('is-visible');
            return;
        }

        observer.observe(target);
    });
}

document.documentElement.classList.add('js-ready');
