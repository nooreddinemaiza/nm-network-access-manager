<!-- Toggle dark mode -->
<button x-data="toggleDark()"
    x-init="init()"
    @click="toggle()"
    class=<?= $class ?? 'p-2 rounded-lg bg-white dark:bg-gray-800 shadow hover:shadow-md transition-all duration-300 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' ?>>
    <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
    </svg>
    <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
</button>
<script>
    function toggleDark() {
        return {
            isDark: localStorage.theme === 'dark',
            toggle() {
                this.isDark = !this.isDark;
                localStorage.theme = this.isDark ? 'dark' : 'light';
                document.documentElement.classList.toggle('dark', this.isDark);
            },
            init() {
                document.documentElement.classList.toggle('dark', this.isDark);
            }
        }
    }
</script>