<script module lang="ts">
    import { reports } from '@/routes';
    import { exportMethod as reportsExport } from '@/routes/reports';

    export const layout = {
        breadcrumbs: [{ title: 'التقارير', href: reports() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
    import { Button } from '@/components/ui/button';
    import { formatCurrency } from '@/lib/currency';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import Download from 'lucide-svelte/icons/download';
    import Scale from 'lucide-svelte/icons/scale';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import type { CategoryBreakdownItem, MonthlySeriesItem, ReportTotals } from '@/types';

    let {
        monthly_series,
        category_breakdown,
        income_breakdown,
        totals,
        currency,
    }: {
        monthly_series: MonthlySeriesItem[];
        category_breakdown: CategoryBreakdownItem[];
        income_breakdown: CategoryBreakdownItem[];
        totals: ReportTotals;
        currency: string;
    } = $props();

    const maxValue = $derived(Math.max(0, ...monthly_series.flatMap((m) => [m.expenses, m.income])));
    const hasChartData = $derived(maxValue > 0);
    const hasBreakdownData = $derived(category_breakdown.some((c) => c.amount > 0));
    const hasIncomeData = $derived(income_breakdown.some((c) => c.amount > 0));

    function barHeight(value: number): number {
        if (maxValue <= 0) return 0;
        if (value <= 0) return 2;
        return Math.max((value / maxValue) * 100, 4);
    }

    function donutGradient(items: CategoryBreakdownItem[]): string {
        const parts = items.filter((c) => c.amount > 0 && c.percentage > 0);
        if (parts.length === 0) return '';
        let acc = 0;
        const segments = parts.map((c) => {
            const from = acc;
            acc += c.percentage;
            return `${c.color ?? '#6B1A2A'} ${from}% ${acc}%`;
        });
        return `conic-gradient(${segments.join(', ')})`;
    }

    const expenseDonut = $derived(donutGradient(category_breakdown));
    const incomeDonut = $derived(donutGradient(income_breakdown));

    const statCards = $derived([
        { label: 'إجمالي المصروفات', value: formatCurrency(totals.total_expenses, currency), icon: TrendingDown, tone: 'default' },
        { label: 'إجمالي الدخل', value: formatCurrency(totals.total_income, currency), icon: TrendingUp, tone: 'default' },
        {
            label: 'صافي الربح',
            value: formatCurrency(totals.net, currency),
            icon: Scale,
            tone: totals.net >= 0 ? 'positive' : 'negative',
        },
        { label: 'متوسط المصروف الشهري', value: formatCurrency(totals.avg_monthly_expenses, currency), icon: BarChart3, tone: 'default' },
    ]);
</script>

<AppHead title="التقارير" />

<div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6" dir="rtl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">التقارير</h1>
            <p class="text-sm text-muted-foreground">تحليل شاملي لإنفاقك ودخلك</p>
        </div>
        <a href={reportsExport().url}>
            <Button variant="outline">
                <Download class="ml-2 size-4" />
                تصدير CSV
            </Button>
        </a>
    </div>

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        {#each statCards as stat}
            <Card class="border border-border bg-card shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm text-muted-foreground">{stat.label}</CardTitle>
                    <div class="flex size-8 items-center justify-center rounded-lg {stat.tone === 'negative' ? 'bg-destructive/10' : stat.tone === 'positive' ? 'bg-success/10' : 'bg-primary/10'}">
                        <stat.icon class="size-4 {stat.tone === 'negative' ? 'text-destructive' : stat.tone === 'positive' ? 'text-success' : 'text-primary'}" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold {stat.tone === 'negative' ? 'text-destructive' : stat.tone === 'positive' ? 'text-success' : ''}">
                        {stat.value}
                    </div>
                </CardContent>
            </Card>
        {/each}
    </div>

    <Card class="border border-border bg-card shadow-sm">
        <CardHeader>
            <CardTitle class="text-base">الاتجاه الشهري</CardTitle>
            <CardDescription>آخر 6 أشهر - الدخل مقابل المصروفات</CardDescription>
        </CardHeader>
        <CardContent>
            {#if !hasChartData}
                <Empty>
                    <EmptyTitle>لا توجد بيانات</EmptyTitle>
                    <EmptyDescription>لا توجد معاملات في هذه الفترة.</EmptyDescription>
                </Empty>
            {:else}
                <div class="mb-4 flex items-center gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1.5">
                        <span class="size-3 rounded-sm bg-success/80"></span>
                        دخل
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="size-3 rounded-sm bg-primary/80"></span>
                        مصروفات
                    </span>
                </div>
                <div class="flex items-end gap-2 sm:gap-4">
                    {#each monthly_series as m}
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="flex h-40 w-full items-end justify-center gap-1">
                                <div
                                    class="w-3 rounded-t bg-success/80 sm:w-4"
                                    style:height="{barHeight(m.income)}%"
                                    title="{m.label}: دخل {formatCurrency(m.income, currency)}"
                                ></div>
                                <div
                                    class="w-3 rounded-t bg-primary/80 sm:w-4"
                                    style:height="{barHeight(m.expenses)}%"
                                    title="{m.label}: مصروفات {formatCurrency(m.expenses, currency)}"
                                ></div>
                            </div>
                            <span class="text-xs text-muted-foreground">{m.label}</span>
                        </div>
                    {/each}
                </div>
            {/if}
        </CardContent>
    </Card>

    <Card class="border border-border bg-card shadow-sm">
        <CardHeader>
            <CardTitle class="text-base">المصروفات حسب الفئة</CardTitle>
            <CardDescription>توزيع إجمالي المصروفات</CardDescription>
        </CardHeader>
        <CardContent>
            {#if !hasBreakdownData}
                <Empty>
                    <EmptyTitle>لا توجد بيانات</EmptyTitle>
                    <EmptyDescription>لا توجد مصروفات مصنفة بعد.</EmptyDescription>
                </Empty>
            {:else}
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div class="relative size-36 shrink-0 rounded-full" style:background={expenseDonut}>
                        <div class="absolute inset-4 rounded-full bg-white shadow-sm dark:bg-card"></div>
                    </div>
                    <ul class="w-full flex-1 space-y-2.5">
                        {#each category_breakdown as cat}
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="size-3 shrink-0 rounded-sm" style:background-color={cat.color ?? '#6B1A2A'}></span>
                                    <span class="truncate font-medium">{cat.name}</span>
                                </span>
                                <span class="shrink-0 text-muted-foreground">
                                    {formatCurrency(cat.amount, currency)} &middot; {cat.percentage}%
                                </span>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}
        </CardContent>
    </Card>

    <Card class="border border-border bg-card shadow-sm">
        <CardHeader>
            <CardTitle class="text-base">الدخل حسب الفئة</CardTitle>
            <CardDescription>توزيع إجمالي الدخل</CardDescription>
        </CardHeader>
        <CardContent>
            {#if !hasIncomeData}
                <Empty>
                    <EmptyTitle>لا توجد بيانات</EmptyTitle>
                    <EmptyDescription>لا يوجد دخل مصنف بعد.</EmptyDescription>
                </Empty>
            {:else}
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div class="relative size-36 shrink-0 rounded-full" style:background={incomeDonut}>
                        <div class="absolute inset-4 rounded-full bg-white shadow-sm dark:bg-card"></div>
                    </div>
                    <ul class="w-full flex-1 space-y-2.5">
                        {#each income_breakdown as cat}
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="size-3 shrink-0 rounded-sm" style:background-color={cat.color ?? '#6B1A2A'}></span>
                                    <span class="truncate font-medium">{cat.name}</span>
                                </span>
                                <span class="shrink-0 text-muted-foreground">
                                    {formatCurrency(cat.amount, currency)} &middot; {cat.percentage}%
                                </span>
                            </li>
                        {/each}
                    </ul>
                </div>
            {/if}
        </CardContent>
    </Card>
</div>
