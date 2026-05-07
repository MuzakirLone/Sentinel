document.addEventListener('DOMContentLoaded', () => {
    // Only run on non-touch devices
    if (window.matchMedia("(pointer: coarse)").matches) return;

    const cursorDot = document.createElement('div');
    const cursorOutline = document.createElement('div');
    
    cursorDot.className = 'custom-cursor-dot';
    cursorOutline.className = 'custom-cursor-outline';

    // Start off-screen — prevent flash at (0,0) on page load
    cursorDot.style.left = '-100px';
    cursorDot.style.top = '-100px';
    cursorOutline.style.left = '-100px';
    cursorOutline.style.top = '-100px';
    
    document.body.appendChild(cursorDot);
    document.body.appendChild(cursorOutline);
    
    // Hide native cursor globally — never toggled off
    document.body.classList.add('has-custom-cursor');
    document.documentElement.classList.add('has-custom-cursor');

    let lastX = 0;
    let lastY = 0;
    let isDraggingScrollbar = false;
    let scrollTarget = null;

    // Check if a mousedown lands on a scrollbar track
    function isOnScrollbar(e) {
        let el = e.target;
        while (el && el !== document.documentElement) {
            if (el.scrollHeight > el.clientHeight) {
                const rect = el.getBoundingClientRect();
                if (e.clientX >= rect.right - 18) {
                    return el;
                }
            }
            if (el.scrollWidth > el.clientWidth) {
                const rect = el.getBoundingClientRect();
                if (e.clientY >= rect.bottom - 18) {
                    return el;
                }
            }
            el = el.parentElement;
        }
        // Viewport scrollbar
        if (e.clientX >= document.documentElement.clientWidth) {
            return document.documentElement;
        }
        return null;
    }

    function setCursorPosition(x, y) {
        cursorDot.style.left = `${x}px`;
        cursorDot.style.top = `${y}px`;
        cursorOutline.animate({
            left: `${x}px`,
            top: `${y}px`
        }, { duration: 40, fill: 'forwards' });
    }

    // Track cursor Y from scroll position during scrollbar drag
    function onScrollDuringDrag() {
        if (!isDraggingScrollbar || !scrollTarget) return;
        const el = scrollTarget;
        const rect = (el === document.documentElement)
            ? { top: 0, height: window.innerHeight, right: window.innerWidth }
            : el.getBoundingClientRect();

        const scrollRange = el.scrollHeight - el.clientHeight;
        if (scrollRange <= 0) return;

        const ratio = el.scrollTop / scrollRange;
        // Map ratio to the visible track area
        const thumbY = rect.top + ratio * (rect.height - 30) + 15;
        const thumbX = rect.right - 3; // near the scrollbar center

        lastX = thumbX;
        lastY = thumbY;
        setCursorPosition(thumbX, thumbY);
    }

    // Handle mouse movement
    window.addEventListener('mousemove', (e) => {
        if (isDraggingScrollbar) return;
        lastX = e.clientX;
        lastY = e.clientY;
        setCursorPosition(lastX, lastY);
    });

    // Handle hover states for interactive elements
    document.body.addEventListener('mouseover', (e) => {
        const interactable = e.target.closest('a, button, input, textarea, select, .nav-item, tr, .card, .feature-card');
        const target = e.target;
        
        // Hide custom cursor when hovering over elements that show native cursor
        const tag = target.tagName.toLowerCase();
        
        let isNativeText = ['input', 'textarea', 'select', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'td', 'th', 'li', 'code', 'pre', 'strong', 'em', 'b', 'i'].includes(tag);
        
        // Include div and label if they primarily contain text
        if ((tag === 'div' || tag === 'label') && target.childNodes.length === 1 && target.childNodes[0].nodeType === Node.TEXT_NODE && target.textContent.trim().length > 0) {
            isNativeText = true;
        }

        const isInteractive = target.closest('a, button, .btn, .nav-item, tr[data-href], .card, .feature-card, .toggle-switch');

        if (isNativeText && !isInteractive) {
            cursorDot.style.opacity = '0';
            cursorOutline.style.opacity = '0';
            document.body.classList.add('force-native-cursor');
        } else {
            cursorDot.style.opacity = '1';
            cursorOutline.style.opacity = '1';
            document.body.classList.remove('force-native-cursor');
            if (isInteractive) {
                cursorOutline.classList.add('cursor-hover');
                cursorDot.classList.add('cursor-hover');
            } else {
                cursorOutline.classList.remove('cursor-hover');
                cursorDot.classList.remove('cursor-hover');
            }
        }
    });

    document.body.addEventListener('mouseout', (e) => {
        // We only care about leaving the document body itself,
        // since mouseover handles all the internal state transitions.
        if (!e.relatedTarget || e.relatedTarget.nodeName === 'HTML') {
            cursorOutline.classList.remove('cursor-hover');
            cursorDot.classList.remove('cursor-hover');
            cursorDot.style.opacity = '1';
            cursorOutline.style.opacity = '1';
        }
    });

    // Mousedown — detect scrollbar click and attach scroll listener
    window.addEventListener('mousedown', (e) => {
        const target = isOnScrollbar(e);
        if (target) {
            isDraggingScrollbar = true;
            scrollTarget = target;
            const scrollEl = (target === document.documentElement) ? window : target;
            scrollEl.addEventListener('scroll', onScrollDuringDrag, { passive: true });
            return;
        }
        cursorOutline.classList.add('cursor-click');
    });
    
    // Mouseup — clean up scrollbar tracking
    window.addEventListener('mouseup', () => {
        if (isDraggingScrollbar && scrollTarget) {
            const scrollEl = (scrollTarget === document.documentElement) ? window : scrollTarget;
            scrollEl.removeEventListener('scroll', onScrollDuringDrag);
            isDraggingScrollbar = false;
            scrollTarget = null;
        }
        cursorOutline.classList.remove('cursor-click');
    });

    // Disable default right-click context menu
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
    });
});
