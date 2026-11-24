// "Get the app" on the website: installs the member app (public/member.webmanifest).
// - Chrome, Edge, Samsung Internet (Android and desktop): the browser's own install prompt.
// - iPhone and iPad (Safari has no prompt) and other browsers: a short how-to (layouts/site.blade.php).
// - Opened as the installed app already: the buttons hide.

export function setupInstall(Alpine) {
    const ua = navigator.userAgent;
    let deferred = null; // the browser's install prompt, kept for when "Get the app" is tapped

    Alpine.store('install', {
        installed: matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
        ready: false, // the browser's prompt is available
        help: false, // the how-to is open
        ios: /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1),

        async start() {
            if (deferred) {
                const prompt = deferred;
                deferred = null;
                this.ready = false;
                prompt.prompt();
                const { outcome } = await prompt.userChoice;
                if (outcome === 'accepted') this.installed = true;
                return;
            }
            this.help = true;
        },
        close() { this.help = false },
    });

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault(); // shown from our button instead (the address bar icon stays)
        deferred = event;
        Alpine.store('install').ready = true;
    });
    window.addEventListener('appinstalled', () => {
        deferred = null;
        Object.assign(Alpine.store('install'), { installed: true, ready: false, help: false });
    });
}
