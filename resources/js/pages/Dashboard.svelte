<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'لوحة التحكم',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ArrowDown from 'lucide-svelte/icons/arrow-down';
    import ArrowUp from 'lucide-svelte/icons/arrow-up';
    import Bot from 'lucide-svelte/icons/bot';
    import CalendarDays from 'lucide-svelte/icons/calendar-days';
    import ListChecks from 'lucide-svelte/icons/list-checks';
    import Minus from 'lucide-svelte/icons/minus';
    import PiggyBank from 'lucide-svelte/icons/piggy-bank';
    import Plus from 'lucide-svelte/icons/plus';
    import Receipt from 'lucide-svelte/icons/receipt';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Wallet from 'lucide-svelte/icons/wallet';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
    import { formatCurrency } from '@/lib/currency';
    import { formatRelativeDate } from '@/lib/date';
    import { toUrl } from '@/lib/utils';
    import { expenses, assistant } from '@/routes';
    import type { TransactionStats, Transaction, CategoryBreakdownItem, QuickStats } from '@/types';

    let {
        stats,
        quick_stats,
        recent_transactions,
        category_breakdown,
    }: {
        stats: TransactionStats;
        quick_stats: QuickStats;
        recent_transactions: Transaction[];
        category_breakdown: CategoryBreakdownItem[];
    } = $props();

    const currency = $derived(stats.currency);

    const user = $derived(page.props.auth.user);
    const firstName = $derived(user?.name.split(' ')[0] ?? '');

    let now = $state(new Date());
    $effect(() => {
        const timer = setInterval(() => (now = new Date()), 30_000);
        return () => clearInterval(timer);
    });

    const todayLabel = $derived(
        new Intl.DateTimeFormat('ar-SA-u-nu-latn', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        }).format(now),
    );
    const timeLabel = $derived(
        new Intl.DateTimeFormat('ar-SA-u-nu-latn', {
            hour: 'numeric',
            minute: '2-digit',
        }).format(now),
    );
    const greeting = $derived(now.getHours() < 12 ? 'صباح الخير' : 'مساء الخير');

    type Trend = {
        direction: 'up' | 'down' | 'flat';
        pct: number | null;
        good: boolean;
    };

    function computeTrend(current: number, previous: number, higherIsBetter: boolean): Trend {
        if (current === previous) {
            return { direction: 'flat', pct: null, good: true };
        }
        const direction = current > previous ? 'up' : 'down';
        const pct = previous > 0 ? Math.abs(((current - previous) / previous) * 100) : null;
        return { direction, pct, good: higherIsBetter ? direction === 'up' : direction === 'down' };
    }

    const incomeTrend = $derived(computeTrend(stats.monthly_income, stats.previous_month_income, true));
    const expensesTrend = $derived(computeTrend(stats.monthly_expenses, stats.previous_month_expenses, false));
    const netTrend = $derived(computeTrend(stats.net_monthly, stats.previous_month_net, true));

    const monthlyCards = $derived([
        { label: 'دخل الشهر', value: formatCurrency(stats.monthly_income, currency), icon: TrendingUp, trend: incomeTrend },
        { label: 'مصروفات الشهر', value: formatCurrency(stats.monthly_expenses, currency), icon: TrendingDown, trend: expensesTrend },
        { label: 'صافي الشهر', value: formatCurrency(stats.net_monthly, currency), icon: PiggyBank, trend: netTrend },
    ]);

    const largestTransaction = $derived(quick_stats.largest_transaction);
    const quickCards = $derived([
        {
            label: 'متوسط الإنفاق اليومي',
            value: formatCurrency(quick_stats.average_daily_expense, currency),
            icon: CalendarDays,
            hint: 'هذا الشهر',
        },
        {
            label: 'أكبر معاملة هذا الشهر',
            value: largestTransaction ? formatCurrency(largestTransaction.amount, currency) : '—',
            icon: Receipt,
            hint: largestTransaction
                ? largestTransaction.description || largestTransaction.vendor || 'بدون وصف'
                : 'لا توجد مصروفات',
        },
        {
            label: 'عدد مصروفات الشهر',
            value: String(quick_stats.expense_count),
            icon: ListChecks,
            hint: 'عمليات سجلت هذا الشهر',
        },
    ]);

    const hoverCard = 'transition-all duration-200 hover:-translate-y-1 hover:shadow-md';
</script>

<AppHead title="لوحة التحكم" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6" dir="rtl">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm text-muted-foreground">
                {todayLabel} &middot; <span class="font-medium text-foreground">{timeLabel}</span>
            </p>
            <h1 class="mt-1 text-xl font-bold md:text-2xl">
                {greeting}، {firstName}
            </h1>
            <p class="text-sm text-muted-foreground">نظرة سريعة على وضعك المالي</p>
        </div>
        <Link href={toUrl(expenses())}>
            <Button>
                <Plus class="ml-2 size-4" />
                إضافة مصروف
            </Button>
        </Link>
    </div>

    <Link href={toUrl(assistant())}>
        <Card class="border border-primary/20 bg-primary/5 shadow-sm transition-colors hover:border-primary/40">
            <CardContent class="flex items-center justify-between gap-3 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                        <Bot class="size-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-bold">اسأل المساعد الذكي</p>
                        <p class="text-sm text-muted-foreground">كم صرفت؟ أضف عملية، أو اطلب تحليل مصروفاتك بالذكاء الاصطناعي.</p>
                    </div>
                </div>
                <span class="shrink-0 text-sm font-semibold text-primary">افتح ←</span>
            </CardContent>
        </Card>
    </Link>

    <Card class="border border-primary/20 bg-primary/5 shadow-sm {hoverCard}">
        <CardContent class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">الرصيد الإجمالي</p>
                <p class="mt-2 text-3xl font-bold text-primary md:text-4xl">
                    {formatCurrency(stats.total_balance, currency)}
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-muted-foreground">
                    <span>
                        إجمالي الدخل:
                        <span class="font-semibold text-success">{formatCurrency(stats.total_income, currency)}</span>
                    </span>
                    <span>
                        إجمالي المصروفات:
                        <span class="font-semibold text-destructive">{formatCurrency(stats.total_expenses, currency)}</span>
                    </span>
                </div>
            </div>
            <div class="text-sm text-muted-foreground">
                صافي الشهر الحالي:
                <span class="font-semibold text-foreground">{formatCurrency(stats.net_monthly, currency)}</span>
            </div>
        </CardContent>
    </Card>

    <div class="grid gap-4 md:grid-cols-3">
        {#each monthlyCards as stat (stat.label)}
            <Card class="h-full border border-border bg-card shadow-sm {hoverCard}">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm text-muted-foreground">{stat.label}</CardTitle>
                    <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                        <stat.icon class="size-4 text-primary" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{stat.value}</div>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-1.5 text-xs font-semibold {stat.trend.good
                            ? 'text-success'
                            : stat.trend.direction === 'flat'
                              ? 'text-muted-foreground'
                              : 'text-destructive'}"
                    >
                        {#if stat.trend.direction === 'up'}
                            <ArrowUp class="size-3.5" />
                        {:else if stat.trend.direction === 'down'}
                            <ArrowDown class="size-3.5" />
                        {:else}
                            <Minus class="size-3.5" />
                        {/if}
                        {#if stat.trend.pct !== null}
                            {stat.trend.pct.toFixed(1)}%
                        {:else}
                            مقارنة جديدة
                        {/if}
                        <span class="font-normal text-muted-foreground">مقارنة بالشهر الماضي</span>
                    </div>
                </CardContent>
            </Card>
        {/each}
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        {#each quickCards as stat (stat.label)}
            <Card class="h-full border border-border bg-card shadow-sm {hoverCard}">
                <CardContent class="flex items-center gap-3 py-4">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                        <stat.icon class="size-4 text-primary" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">{stat.label}</p>
                        <p class="truncate text-base font-bold">{stat.value}</p>
                        <p class="truncate text-xs text-muted-foreground">{stat.hint}</p>
                    </div>
                </CardContent>
            </Card>
        {/each}
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <Card class="h-full border border-border bg-card shadow-sm {hoverCard}">
                <CardHeader>
                    <CardTitle class="text-base">آخر المعاملات</CardTitle>
                    <CardDescription>أحدث المعاملات عبر جميع الحسابات</CardDescription>
                </CardHeader>
                <CardContent>
                    {#if recent_transactions.length === 0}
                        <Empty>
                            <EmptyTitle>لا توجد معاملات</EmptyTitle>
                            <EmptyDescription>سجّل أول معاملة لتبدأ بتتبع مصاريفك.</EmptyDescription>
                        </Empty>
                    {:else}
                        <div class="space-y-3">
                            {#each recent_transactions as tx (tx.id)}
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                            <Receipt class="size-4 text-primary" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{tx.description || tx.vendor || 'بدون وصف'}</p>
                                            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                {#if tx.category}
                                                    <span class="flex items-center gap-1.5">
                                                        <span class="size-2 rounded-full" style:background-color={tx.category.color ?? '#9ca3af'}></span>
                                                        {tx.category.name}
                                                    </span>
                                                {:else}
                                                    <span>بدون تصنيف</span>
                                                {/if}
                                                <span>&middot; {formatRelativeDate(tx.date)}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold {tx.type === 'income' ? 'text-success' : 'text-foreground'}">
                                        {tx.type === 'income' ? '+' : '-'}{formatCurrency(tx.amount, currency)}
                                    </span>
                                </div>
                            {/each}
                        </div>
                        <Link href={toUrl(expenses())} class="mt-3 inline-block">
                            <Button variant="link" class="h-auto p-0 text-sm">عرض الكل</Button>
                        </Link>
                    {/if}
                </CardContent>
            </Card>
        </div>

        <div>
            <Card class="h-full border border-border bg-card shadow-sm {hoverCard}">
                <CardHeader>
                    <CardTitle class="text-base">المصروفات حسب الفئة</CardTitle>
                    <CardDescription>توزيع هذا الشهر</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    {#if category_breakdown.length === 0}
                        <Empty>
                            <EmptyTitle>لا توجد بيانات</EmptyTitle>
                            <EmptyDescription>لا توجد مصروفات لهذا الشهر بعد.</EmptyDescription>
                        </Empty>
                    {:else}
                        {#each category_breakdown as cat (cat.name)}
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium">{cat.name}</span>
                                    <span class="text-muted-foreground">
                                        {formatCurrency(cat.amount, currency)} &middot; {cat.percentage}%
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        style="width: {Math.min(cat.percentage, 100)}%; background-color: {cat.color ?? '#6B1A2A'}"
                                    ></div>
                                </div>
                            </div>
                        {/each}
                    {/if}
                </CardContent>
            </Card>
        </div>
    </div>
</div>