<script module lang="ts">
    import { budgets as budgetsRoute } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'الميزانيات', href: budgetsRoute() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import BudgetController from '@/actions/App/Http/Controllers/BudgetController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import {
        AlertDialog,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
    import { formatCurrency } from '@/lib/currency';
    import Briefcase from 'lucide-svelte/icons/briefcase';
    import Building from 'lucide-svelte/icons/building';
    import CircleDot from 'lucide-svelte/icons/circle-dot';
    import Code from 'lucide-svelte/icons/code';
    import Megaphone from 'lucide-svelte/icons/megaphone';
    import MoreHorizontal from 'lucide-svelte/icons/more-horizontal';
    import Pencil from 'lucide-svelte/icons/pencil';
    import Plane from 'lucide-svelte/icons/plane';
    import Plus from 'lucide-svelte/icons/plus';
    import Settings from 'lucide-svelte/icons/settings';
    import Shield from 'lucide-svelte/icons/shield';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Users from 'lucide-svelte/icons/users';
    import UtensilsCrossed from 'lucide-svelte/icons/utensils-crossed';
    import Wrench from 'lucide-svelte/icons/wrench';
    import Zap from 'lucide-svelte/icons/zap';
    import WalletCards from 'lucide-svelte/icons/wallet-cards';
    import type { Component, SvelteComponent } from 'svelte';

    type BudgetCategory = {
        id: number;
        name: string;
        color: string | null;
        icon: string | null;
    };

    type BudgetItem = {
        id: number;
        category: BudgetCategory | null;
        amount: number;
        spent: number;
        remaining: number;
        percentage: number;
        status: 'ok' | 'warning' | 'over';
        currency: string;
    };

    let {
        budgets,
        available_categories,
        month,
        currency,
    }: {
        budgets: BudgetItem[];
        available_categories: BudgetCategory[];
        month: string;
        currency: string;
    } = $props();

    type CategoryIcon =
        | Component<{ class?: string }>
        | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

    const ICONS: Record<string, CategoryIcon> = {
        code: Code,
        settings: Settings,
        'utensils-crossed': UtensilsCrossed,
        plane: Plane,
        building: Building,
        users: Users,
        megaphone: Megaphone,
        zap: Zap,
        wrench: Wrench,
        shield: Shield,
        briefcase: Briefcase,
        'shopping-cart': ShoppingCart,
        'trending-up': TrendingUp,
        'more-horizontal': MoreHorizontal,
    };
    const DEFAULT_ICON: CategoryIcon = CircleDot;

    function resolveIcon(icon: string | null): CategoryIcon {
        return (icon && ICONS[icon]) || DEFAULT_ICON;
    }

    function onMonthChange(event: Event) {
        const value = (event.currentTarget as HTMLInputElement).value;
        if (value) {
            router.get(
                budgetsRoute().url,
                { month: value },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }
    }

    const totalBudget = $derived(budgets.reduce((sum, b) => sum + Number(b.amount), 0));
    const totalSpent = $derived(budgets.reduce((sum, b) => sum + Number(b.spent), 0));
    const totalRemaining = $derived(totalBudget - totalSpent);

    let dialogOpen = $state(false);
    let editing: BudgetItem | null = $state(null);
    let deleteTarget: BudgetItem | null = $state(null);
    let selectedCategory = $state('');

    function openCreate() {
        editing = null;
        selectedCategory = available_categories[0] ? String(available_categories[0].id) : '';
        dialogOpen = true;
    }

    function openEdit(budget: BudgetItem) {
        editing = budget;
        selectedCategory = budget.category ? String(budget.category.id) : '';
        dialogOpen = true;
    }

    function handleDialogOpenChange(value: boolean) {
        dialogOpen = value;
        if (!value) {
            editing = null;
        }
    }
</script>

<AppHead title="الميزانيات" />

<div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6" dir="rtl">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-bold">الميزانيات</h1>
            <p class="text-sm text-muted-foreground">حدد سقفاً شهرياً للإنفاق لكل فئة وتتبّع التقدم</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <Input type="month" value={month} onchange={onMonthChange} class="w-44" aria-label="الشهر" />
            <Button onclick={openCreate}>
                <Plus class="ml-2 size-4" />
                إضافة ميزانية
            </Button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <Card class="border border-border bg-card shadow-sm">
            <CardContent class="flex items-center gap-3 py-4">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                    <WalletCards class="size-4 text-primary" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">إجمالي الميزانية</p>
                    <p class="text-base font-bold">{formatCurrency(totalBudget, currency)}</p>
                </div>
            </CardContent>
        </Card>
        <Card class="border border-border bg-card shadow-sm">
            <CardContent class="flex items-center gap-3 py-4">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-destructive/10">
                    <TrendingUp class="size-4 text-destructive" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">إجمالي المصروف</p>
                    <p class="text-base font-bold">{formatCurrency(totalSpent, currency)}</p>
                </div>
            </CardContent>
        </Card>
        <Card class="border border-border bg-card shadow-sm">
            <CardContent class="flex items-center gap-3 py-4">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-success/10">
                    <WalletCards class="size-4 text-success" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">المتبقي</p>
                    <p class="text-base font-bold {totalRemaining < 0 ? 'text-destructive' : ''}">
                        {formatCurrency(totalRemaining, currency)}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>

    {#if budgets.length === 0}
        <Card class="border border-dashed border-border bg-card shadow-sm">
            <CardContent class="py-10">
                <Empty>
                    <EmptyTitle>لا توجد ميزانيات لهذا الشهر</EmptyTitle>
                    <EmptyDescription>أضف ميزانية شهرية لكل فئة لتبدأ بتتبع إنفاقك والتحكم به.</EmptyDescription>
                </Empty>
            </CardContent>
        </Card>
    {:else}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {#each budgets as budget}
                {@const Icon = resolveIcon(budget.category?.icon ?? null)}
                <Card class="border border-border bg-card shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                                style:background-color={budget.category?.color ?? '#6B1A2A'}
                            >
                                <Icon class="size-4 text-white" />
                            </div>
                            <div class="min-w-0">
                                <CardTitle class="truncate text-sm">{budget.category?.name ?? 'بدون فئة'}</CardTitle>
                                <CardDescription class="text-xs">
                                    {formatCurrency(budget.spent, currency)} من {formatCurrency(budget.amount, currency)}
                                </CardDescription>
                            </div>
                        </div>
                        {#if budget.status === 'over'}
                            <Badge variant="destructive" class="shrink-0">تجاوز</Badge>
                        {:else if budget.status === 'warning'}
                            <Badge variant="outline" class="shrink-0 border-amber-500 text-amber-600 dark:text-amber-400">قارب على الانتهاء</Badge>
                        {:else}
                            <Badge variant="outline" class="shrink-0 text-muted-foreground">ضمن الحد</Badge>
                        {/if}
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                <span>{budget.percentage}%</span>
                                <span>متبقي {formatCurrency(budget.remaining, currency)}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                <div
                                    class="h-full rounded-full transition-all"
                                    style="width: {Math.min(budget.percentage, 100)}%; background-color: {budget.status === 'over'
                                        ? 'var(--destructive)'
                                        : budget.status === 'warning'
                                          ? 'var(--accent)'
                                          : 'var(--primary)'}"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-0.5">
                            <Button variant="ghost" size="icon-sm" title="تعديل" onclick={() => openEdit(budget)}>
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="حذف"
                                class="text-destructive hover:text-destructive"
                                onclick={() => (deleteTarget = budget)}
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            {/each}
        </div>
    {/if}
</div>

<Dialog open={dialogOpen} onOpenChange={handleDialogOpenChange}>
    <DialogContent class="sm:max-w-md">
        {#if editing !== null}
            <Form {...BudgetController.update.form(editing.id)} class="space-y-4" options={{ preserveScroll: true }} onSuccess={() => (dialogOpen = false)}>
                {#snippet children({ errors, processing })}
                    <DialogHeader>
                        <DialogTitle>تعديل الميزانية</DialogTitle>
                        <DialogDescription>{editing.category?.name}</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="budget-amount">حد الميزانية</Label>
                        <Input id="budget-amount" type="number" name="amount" step="0.01" min="0" value={editing.amount} required />
                        <InputError message={errors.amount} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="budget-month">الشهر</Label>
                        <Input id="budget-month" type="month" name="month" value={month} required />
                        <InputError message={errors.month} />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" onclick={() => (dialogOpen = false)}>إلغاء</Button>
                        <Button type="submit" disabled={processing}>حفظ التعديلات</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {:else}
            <Form {...BudgetController.store.form()} class="space-y-4" options={{ preserveScroll: true }} onSuccess={() => (dialogOpen = false)}>
                {#snippet children({ errors, processing })}
                    <DialogHeader>
                        <DialogTitle>إضافة ميزانية</DialogTitle>
                        <DialogDescription>حدد سقفاً شهرياً للإنفاق على فئة معينة</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="budget-category">الفئة</Label>
                        {#if available_categories.length === 0}
                            <p class="rounded-lg border border-dashed border-border p-3 text-center text-sm text-muted-foreground">
                                جميع الفئات لديها ميزانية لهذا الشهر.
                            </p>
                        {:else}
                            <NativeSelect bind:value={selectedCategory} id="budget-category" name="category_id">
                                {#each available_categories as cat}
                                    <NativeSelectOption value={cat.id}>{cat.name}</NativeSelectOption>
                                {/each}
                            </NativeSelect>
                        {/if}
                        <InputError message={errors.category_id} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="budget-amount-new">حد الميزانية</Label>
                        <Input id="budget-amount-new" type="number" name="amount" step="0.01" min="0" placeholder="5000" required />
                        <InputError message={errors.amount} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="budget-month-new">الشهر</Label>
                        <Input id="budget-month-new" type="month" name="month" value={month} required />
                        <InputError message={errors.month} />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" onclick={() => (dialogOpen = false)}>إلغاء</Button>
                        <Button type="submit" disabled={processing || available_categories.length === 0}>إضافة</Button>
                    </DialogFooter>
                {/snippet}
            </Form>
        {/if}
    </DialogContent>
</Dialog>

<AlertDialog
    open={deleteTarget !== null}
    onOpenChange={(value) => {
        if (!value) deleteTarget = null;
    }}
>
    <AlertDialogContent>
        {#if deleteTarget !== null}
            {@const target = deleteTarget}
            <Form {...BudgetController.destroy.form(target.id)} onSuccess={() => (deleteTarget = null)}>
                {#snippet children({ processing })}
                    <AlertDialogHeader>
                        <AlertDialogTitle>حذف الميزانية؟</AlertDialogTitle>
                        <AlertDialogDescription>
                            سيتم حذف ميزانية «{target.category?.name ?? 'بدون فئة'}» لهذا الشهر. لا يمكن التراجع عن هذا
                            الإجراء.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="gap-2">
                        <AlertDialogCancel onclick={() => (deleteTarget = null)}>إلغاء</AlertDialogCancel>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            حذف
                        </Button>
                    </AlertDialogFooter>
                {/snippet}
            </Form>
        {/if}
    </AlertDialogContent>
</AlertDialog>
