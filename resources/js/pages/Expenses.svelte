<script module lang="ts">
    import { expenses } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'المصروفات', href: expenses() }],
    };
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
    import AppHead from '@/components/AppHead.svelte';
    import TransactionDialog from '@/components/TransactionDialog.svelte';
    import {
        AlertDialog,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
    import { Input } from '@/components/ui/input';
    import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
    import { formatCurrency } from '@/lib/currency';
    import { formatRelativeDate } from '@/lib/date';
    import { expenses as expensesRoute } from '@/routes';
    import Pencil from 'lucide-svelte/icons/pencil';
    import Plus from 'lucide-svelte/icons/plus';
    import Receipt from 'lucide-svelte/icons/receipt';
    import Search from 'lucide-svelte/icons/search';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import type { Transaction, TransactionCategory } from '@/types';

    let {
        transactions,
        categories,
        currency,
        filters,
    }: {
        transactions: Transaction[];
        categories: TransactionCategory[];
        currency: string;
        filters: { search: string | null; category: number | null; sort: string | null };
    } = $props();

    let search = $state((() => filters?.search ?? '')());
    let category = $state((() => filters?.category ?? '')());
    let sort = $state((() => filters?.sort ?? 'date-desc')());
    let debounceTimer: ReturnType<typeof setTimeout> | undefined = $state();

    function applyFilters() {
        router.get(
            expensesRoute().url,
            {
                search: search.trim() || undefined,
                category: category === '' ? undefined : Number(category),
                sort: sort || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function handleSearchInput() {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(applyFilters, 400);
    }

    function handleFilterChange() {
        window.clearTimeout(debounceTimer);
        applyFilters();
    }

    let dialogOpen = $state(false);
    let editing: Transaction | null = $state(null);
    let deleteId: number | null = $state(null);

    function openCreate() {
        editing = null;
        dialogOpen = true;
    }

    function openEdit(tx: Transaction) {
        editing = tx;
        dialogOpen = true;
    }

    function handleDialogOpenChange(value: boolean) {
        dialogOpen = value;
        if (!value) {
            editing = null;
        }
    }
</script>

<AppHead title="المصروفات" />

<div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6" dir="rtl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">المصروفات</h1>
            <p class="text-sm text-muted-foreground">جميع معاملاتك المالية</p>
        </div>
        <Button onclick={openCreate}>
            <Plus class="ml-2 size-4" />
            إضافة مصروف
        </Button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="relative max-w-sm flex-1">
            <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                bind:value={search}
                oninput={handleSearchInput}
                placeholder="ابحث عن مصروف..."
                class="pr-10"
            />
        </div>
        <NativeSelect bind:value={category} onchange={handleFilterChange} class="w-44">
            <NativeSelectOption value="">كل الفئات</NativeSelectOption>
            {#each categories as cat}
                <NativeSelectOption value={cat.id}>{cat.name}</NativeSelectOption>
            {/each}
        </NativeSelect>
        <NativeSelect bind:value={sort} onchange={handleFilterChange} class="w-44">
            <NativeSelectOption value="date-desc">الأحدث أولاً</NativeSelectOption>
            <NativeSelectOption value="date-asc">الأقدم أولاً</NativeSelectOption>
            <NativeSelectOption value="amount-desc">المبلغ تنازلياً</NativeSelectOption>
            <NativeSelectOption value="amount-asc">المبلغ تصاعدياً</NativeSelectOption>
        </NativeSelect>
    </div>

    <Card class="border border-border bg-card shadow-sm">
        <CardHeader class="pb-3">
            <CardTitle class="text-base">كل المصروفات</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            {#if transactions.length === 0}
                <div class="p-6">
                    {#if search.trim()}
                        <Empty>
                            <EmptyTitle>لا توجد نتائج</EmptyTitle>
                            <EmptyDescription>لا توجد مصروفات مطابقة لبحث «{search}».</EmptyDescription>
                        </Empty>
                    {:else}
                        <Empty>
                            <EmptyTitle>لا توجد مصروفات</EmptyTitle>
                            <EmptyDescription>ابدأ بتسجيل أول مصروف لك.</EmptyDescription>
                        </Empty>
                    {/if}
                </div>
            {:else}
                <div class="divide-y divide-border">
                    {#each transactions as tx}
                        <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-secondary/50">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <Receipt class="size-4 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{tx.description || tx.vendor || 'بدون وصف'}</p>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        {#if tx.category}
                                            <span class="flex items-center gap-1.5">
                                                <span class="size-2 rounded-full" style:background-color={tx.category.color ?? '#9ca3af'}></span>
                                                {tx.category.name}
                                            </span>
                                        {:else}
                                            <span>بدون تصنيف</span>
                                        {/if}
                                        {#if tx.vendor}
                                            <span>&middot; {tx.vendor}</span>
                                        {/if}
                                        <span>&middot; {formatRelativeDate(tx.date)}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span class="text-sm font-semibold">-{formatCurrency(tx.amount, currency)}</span>
                                <Button variant="ghost" size="icon-sm" title="تعديل" onclick={() => openEdit(tx)}>
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    title="حذف"
                                    class="text-destructive hover:text-destructive"
                                    onclick={() => (deleteId = tx.id)}
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </CardContent>
    </Card>
</div>

<TransactionDialog
    type="expense"
    {categories}
    transaction={editing}
    bind:open={dialogOpen}
    onOpenChange={handleDialogOpenChange}
/>

<AlertDialog
    open={deleteId !== null}
    onOpenChange={(value) => {
        if (!value) deleteId = null;
    }}
>
    <AlertDialogContent>
        {#if deleteId !== null}
            <Form
                {...TransactionController.destroy.form(deleteId)}
                onSuccess={() => (deleteId = null)}
            >
                {#snippet children({ processing })}
                    <AlertDialogHeader>
                        <AlertDialogTitle>حذف المعاملة؟</AlertDialogTitle>
                        <AlertDialogDescription>
                            هل أنت متأكد من حذف هذه المعاملة؟ لا يمكن التراجع عن هذا الإجراء.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="gap-2">
                        <AlertDialogCancel onclick={() => (deleteId = null)}>إلغاء</AlertDialogCancel>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            حذف
                        </Button>
                    </AlertDialogFooter>
                {/snippet}
            </Form>
        {/if}
    </AlertDialogContent>
</AlertDialog>
