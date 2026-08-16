<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { home, login, register } from '@/routes';

    let {
        title = '',
        children,
    }: {
        title?: string;
        children?: Snippet;
    } = $props();

    const auth = $derived(page.props.auth);
</script>

<AppHead {title} />

<div class="flex min-h-screen flex-col bg-background" dir="rtl">
    <header class="sticky top-0 z-50 border-b border-border bg-background/90 backdrop-blur-sm">
        <div class="mx-auto flex h-14 items-center justify-between px-4 md:max-w-6xl">
            <Link href={toUrl(home())} class="flex items-center gap-2 text-base font-bold text-foreground">
                <svg class="size-6 text-primary" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="6" fill="currentColor"/>
                    <path d="M8 22V14L16 8L24 14V22H20V16L16 12L12 16V22H8Z" fill="white"/>
                </svg>
                كاش سينس
            </Link>
            <nav class="hidden items-center gap-6 md:flex">
                <Link href={toUrl(home())} class="text-sm text-muted-foreground transition-colors hover:text-foreground">الرئيسية</Link>
                <Link href="/pricing" class="text-sm text-muted-foreground transition-colors hover:text-foreground">الأسعار</Link>
                <Link href="/docs" class="text-sm text-muted-foreground transition-colors hover:text-foreground">المساعدة</Link>
                <Link href="/contact" class="text-sm text-muted-foreground transition-colors hover:text-foreground">اتصل بنا</Link>
            </nav>
            <div class="flex items-center gap-2">
                {#if auth.user}
                    <Link href="/dashboard">
                        <Button variant="default" size="sm">لوحة التحكم</Button>
                    </Link>
                {:else}
                    <Link href={toUrl(login())}>
                        <Button variant="ghost" size="sm">دخول</Button>
                    </Link>
                    <Link href={toUrl(register())}>
                        <Button variant="default" size="sm">ابدأ مجاناً</Button>
                    </Link>
                {/if}
            </div>
        </div>
    </header>

    <main class="flex-1" dir="rtl">
        {@render children?.()}
    </main>

    <footer class="border-t border-border bg-card text-right" dir="rtl">
        <div class="mx-auto grid gap-6 px-4 py-10 md:max-w-6xl md:grid-cols-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm font-bold">
                    <svg class="size-5 text-primary" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="6" fill="currentColor"/>
                        <path d="M8 22V14L16 8L24 14V22H20V16L16 12L12 16V22H8Z" fill="white"/>
                    </svg>
                    كاش سينس
                </div>
                <p class="text-sm text-muted-foreground">تتبع المصروفات بالذكاء الاصطناعي للشركات الصغيرة.</p>
            </div>
            <div class="space-y-2">
                <h4 class="text-sm font-bold">المنتج</h4>
                <ul class="space-y-1.5 text-sm text-muted-foreground">
                    <li><Link href="/pricing" class="transition-colors hover:text-foreground">الأسعار</Link></li>
                    <li><Link href="/docs" class="transition-colors hover:text-foreground">المساعدة</Link></li>
                </ul>
            </div>
            <div class="space-y-2">
                <h4 class="text-sm font-bold">الشركة</h4>
                <ul class="space-y-1.5 text-sm text-muted-foreground">
                    <li><Link href="/contact" class="transition-colors hover:text-foreground">اتصل بنا</Link></li>
                    <li><span class="cursor-default">سياسة الخصوصية</span></li>
                    <li><span class="cursor-default">شروط الخدمة</span></li>
                </ul>
            </div>
            <div class="space-y-2">
                <h4 class="text-sm font-bold">روابط</h4>
                <ul class="space-y-1.5 text-sm text-muted-foreground">
                    <li><Link href="/docs" class="transition-colors hover:text-foreground">مركز المساعدة</Link></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-border px-4 py-3 text-center">
            <p class="text-xs text-muted-foreground">&copy; {new Date().getFullYear()} كاش سينس. جميع الحقوق محفوظة.</p>
        </div>
    </footer>
</div>

<Toaster />