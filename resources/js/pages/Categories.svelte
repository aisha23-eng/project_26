<script module lang="ts">
    import { categories as categoriesRoute } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'الفئات', href: categoriesRoute() }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
    import InputError from '@/components/InputError.svelte';
    import AppHead from '@/components/AppHead.svelte';
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
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import type { CategoryItem, TransactionType } from '@/types';
    import Banknote from 'lucide-svelte/icons/banknote';
    import Briefcase from 'lucide-svelte/icons/briefcase';
    import Building from 'lucide-svelte/icons/building';
    import CircleDot from 'lucide-svelte/icons/circle-dot';
    import Code from 'lucide-svelte/icons/code';
    import Gift from 'lucide-svelte/icons/gift';
    import Laptop from 'lucide-svelte/icons/laptop';
    import Megaphone from 'lucide-svelte/icons/megaphone';
    import MoreHorizontal from 'lucide-svelte/icons/more-horizontal';
    import Pencil from 'lucide-svelte/icons/pencil';
    import Plane from 'lucide-svelte/icons/plane';
    import Plus from 'lucide-svelte/icons/plus';
    import Shield from 'lucide-svelte/icons/shield';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Users from 'lucide-svelte/icons/users';
    import UtensilsCrossed from 'lucide-svelte/icons/utensils-crossed';
    import Wrench from 'lucide-svelte/icons/wrench';
    import Zap from 'lucide-svelte/icons/zap';
    import type { Component, SvelteComponent } from 'svelte';

    let {
        categories,
    }: {
        categories: CategoryItem[];
        currency: string;
    } = $props();

    type CategoryIcon =
        | Component<{ class?: string }>
        | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

    const ICONS: Record<string, CategoryIcon> = {
        'shopping-cart': ShoppingCart,
        code: Code,
        plane: Plane,
        users: Users,
        zap: Zap,
        'utensils-crossed': UtensilsCrossed,
        building: Building,
        briefcase: Briefcase,
        'trending-up': TrendingUp,
        gift: Gift,
        laptop: Laptop,
        banknote: Banknote,
        shield: Shield,
        wrench: Wrench,
        megaphone: Megaphone,
        'more-horizontal': MoreHorizontal,
    };
    const ICON_CHOICES = Object.keys(ICONS);
    const DEFAULT_ICON: CategoryIcon = CircleDot;

    const PRESET_COLORS = ['#6B1A2A', '#b91c1c', '#d97706', '#047857', '#1d4ed8', '#7c3aed', '#db2777', '#0d9488', '#475569'];

    function resolveIcon(icon: string | null): CategoryIcon {
        return (icon && ICONS[icon]) || DEFAULT_ICON;
    }

    const expenseCategories = $derived(categories.filter((c) => c.type === 'expense'));
    const incomeCategories = $derived(categories.filter((c) => c.type === 'income'));
    const hasCustomExpense = $derived(expenseCategories.some((c) => !c.is_system));
    const hasCustomIncome = $derived(incomeCategories.some((c) => !c.is_system));

    let dialogOpen = $state(false);
    let editingCategory: CategoryItem | null = $state(null);
    let dialogType: TransactionType = $state('expense');
    let selectedIcon = $state('shopping-cart');
    let selectedColor = $state('#6B1A2A');
    let deleteTarget: CategoryItem | null = $state(null);

    let formKey = $state(0);
    let wasOpen = false;

    $effect(() => {
        if (dialogOpen && !wasOpen) {
            formKey += 1;
        }
        wasOpen = dialogOpen;
    });

    function openCreate(type: TransactionType) {
        dialogType = type;
        editingCategory = null;
        selectedIcon = 'shopping-cart';
        selectedColor = '#6B1A2A';
        dialogOpen = true;
    }

    function openEdit(cat: CategoryItem) {
        dialogType = cat.type;
        editingCategory = cat;
        selectedIcon = cat.icon || 'shopping-cart';
        selectedColor = cat.color || '#6B1A2A';
        dialogOpen = true;
    }

    function handleDialogOpenChange(value: boolean) {
        dialogOpen = value;
        if (!value) {
            editingCategory = null;
        }
    }

    const dialogTitle = $derived(
        editingCategory !== null
            ? dialogType === 'expense'
                ? 'تعديل فئة مصروفات'
                : 'تعديل فئة دخل'
            : dialogType === 'expense'
              ? 'إضافة فئة مصروفات'
              : 'إضافة فئة دخل',
    );
</script>

<AppHead title="الفئات" />

<div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6" dir="rtl">
    <div>
        <h1 class="text-xl font-bold">الفئات</h1>
        <p class="text-sm text-muted-foreground">نظّم مصروفاتك ودخلك بفئات واضحة</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <Card class="border border-border bg-card shadow-sm">
            <CardHeader class="flex flex-row items-center justify-between gap-3">
                <CardTitle class="text-base">فئات المصروفات</CardTitle>
                <Button size="sm" variant="outline" onclick={() => openCreate('expense')}>
                    <Plus class="ml-1.5 size-4" />
                    إضافة
                </Button>
            </CardHeader>
            <CardContent>
                {#if !hasCustomExpense}
                    <p class="mb-3 rounded-lg border border-dashed border-border p-3 text-center text-sm text-muted-foreground">
                        لم تضف فئات مخصصة بعد - أضف فئتك الأولى الآن.
                    </p>
                {/if}
                <div class="grid gap-3 sm:grid-cols-2">
                    {#each expenseCategories as cat}
                        {@const Icon = resolveIcon(cat.icon)}
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3 shadow-sm">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg" style:background-color={cat.color || '#6B1A2A'}>
                                    <Icon class="size-4 text-white" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-medium">{cat.name}</p>
                                        <Badge variant="outline" class="shrink-0 text-[10px]">
                                            {cat.is_system ? 'نظامية' : 'مخصصة'}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">{cat.expenses_count} معاملة</p>
                                </div>
                            </div>
                            {#if !cat.is_system}
                                <div class="flex shrink-0 items-center gap-0.5">
                                    <Button variant="ghost" size="icon-sm" title="تعديل" onclick={() => openEdit(cat)}>
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        title="حذف"
                                        class="text-destructive hover:text-destructive"
                                        onclick={() => (deleteTarget = cat)}
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            {/if}
                        </div>
                    {/each}
                </div>
            </CardContent>
        </Card>

        <Card class="border border-border bg-card shadow-sm">
            <CardHeader class="flex flex-row items-center justify-between gap-3">
                <CardTitle class="text-base">فئات الدخل</CardTitle>
                <Button size="sm" variant="outline" onclick={() => openCreate('income')}>
                    <Plus class="ml-1.5 size-4" />
                    إضافة
                </Button>
            </CardHeader>
            <CardContent>
                {#if !hasCustomIncome}
                    <p class="mb-3 rounded-lg border border-dashed border-border p-3 text-center text-sm text-muted-foreground">
                        لم تضف فئات مخصصة بعد - أضف فئتك الأولى الآن.
                    </p>
                {/if}
                <div class="grid gap-3 sm:grid-cols-2">
                    {#each incomeCategories as cat}
                        {@const Icon = resolveIcon(cat.icon)}
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3 shadow-sm">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg" style:background-color={cat.color || '#6B1A2A'}>
                                    <Icon class="size-4 text-white" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-medium">{cat.name}</p>
                                        <Badge variant="outline" class="shrink-0 text-[10px]">
                                            {cat.is_system ? 'نظامية' : 'مخصصة'}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">{cat.expenses_count} معاملة</p>
                                </div>
                            </div>
                            {#if !cat.is_system}
                                <div class="flex shrink-0 items-center gap-0.5">
                                    <Button variant="ghost" size="icon-sm" title="تعديل" onclick={() => openEdit(cat)}>
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        title="حذف"
                                        class="text-destructive hover:text-destructive"
                                        onclick={() => (deleteTarget = cat)}
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            {/if}
                        </div>
                    {/each}
                </div>
            </CardContent>
        </Card>
    </div>
</div>

<Dialog open={dialogOpen} onOpenChange={handleDialogOpenChange}>
    <DialogContent class="sm:max-w-md">
        {#key `${editingCategory?.id ?? 'new'}:${formKey}`}
            <DialogHeader>
                <DialogTitle>{dialogTitle}</DialogTitle>
                <DialogDescription>اختر رمزاً ولوناً يميزان الفئة</DialogDescription>
            </DialogHeader>

            {#if editingCategory !== null}
                <Form {...CategoryController.update.form(editingCategory.id)} class="space-y-4" options={{ preserveScroll: true }} onSuccess={() => (dialogOpen = false)}>
                    {#snippet children({ errors, processing })}
                        {@render categoryFields({ errors, processing })}
                    {/snippet}
                </Form>
            {:else}
                <Form {...CategoryController.store.form()} class="space-y-4" options={{ preserveScroll: true }} onSuccess={() => (dialogOpen = false)}>
                    {#snippet children({ errors, processing })}
                        {@render categoryFields({ errors, processing })}
                    {/snippet}
                </Form>
            {/if}
        {/key}
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
            <Form {...CategoryController.destroy.form(target.id)} onSuccess={() => (deleteTarget = null)}>
                {#snippet children({ processing })}
                    <AlertDialogHeader>
                        <AlertDialogTitle>حذف الفئة؟</AlertDialogTitle>
                        <AlertDialogDescription>
                            سيتم حذف فئة «{target.name}» وإزالة التصنيف عن معاملاتها. لا يمكن التراجع عن هذا الإجراء.
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

{#snippet categoryFields({ errors, processing }: { errors: Record<string, string>; processing: boolean })}
    <div class="grid gap-2">
        <Label for="category-name">اسم الفئة</Label>
        <Input id="category-name" name="name" value={editingCategory?.name ?? ''} placeholder="مثال: توصيل ومواصلات" required />
        <InputError message={errors.name} />
    </div>

    <input type="hidden" name="type" value={dialogType} />
    <input type="hidden" name="icon" value={selectedIcon} />
    <input type="hidden" name="color" value={selectedColor} />

    <div class="grid gap-2">
        <Label>الرمز</Label>
        <div class="grid grid-cols-8 gap-1.5">
            {#each ICON_CHOICES as iconName}
                {@const Icon = ICONS[iconName]}
                <button
                    type="button"
                    title={iconName}
                    onclick={() => (selectedIcon = iconName)}
                    class="flex size-8 items-center justify-center rounded-lg border transition-colors {selectedIcon === iconName ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:bg-secondary'}"
                >
                    <Icon class="size-4" />
                </button>
            {/each}
        </div>
        <InputError message={errors.icon} />
    </div>

    <div class="grid gap-2">
        <Label>اللون</Label>
        <div class="flex flex-wrap items-center gap-2">
            {#each PRESET_COLORS as color}
                <button
                    type="button"
                    aria-label={color}
                    onclick={() => (selectedColor = color)}
                    class="size-7 rounded-lg border-2 transition-transform {selectedColor === color ? 'scale-110 border-foreground' : 'border-transparent'}"
                    style:background-color={color}
                ></button>
            {/each}
        </div>
        <InputError message={errors.color} />
    </div>

    {#if editingCategory === null}
        <div class="grid gap-2">
            <Label for="category-description">الوصف (اختياري)</Label>
            <Textarea id="category-description" name="description" placeholder="وصف مختصر للفئة" />
            <InputError message={errors.description} />
        </div>
    {/if}

    <DialogFooter class="gap-2">
        <Button type="button" variant="outline" onclick={() => (dialogOpen = false)}>إلغاء</Button>
        <Button type="submit" disabled={processing}>{editingCategory !== null ? 'حفظ التعديلات' : 'إضافة'}</Button>
    </DialogFooter>
{/snippet}
