<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
    import type { Transaction, TransactionCategory, TransactionType } from '@/types';

    let {
        type,
        categories,
        transaction = null,
        open = $bindable(false),
        onOpenChange,
    }: {
        type: TransactionType;
        categories: TransactionCategory[];
        transaction?: Transaction | null;
        open?: boolean;
        onOpenChange?: (open: boolean) => void;
    } = $props();

    const isEditing = $derived(transaction !== null);
    const todayISO = new Date().toISOString().slice(0, 10);
    const defaultDate = $derived(transaction ? transaction.date : todayISO);
    const defaultCategoryId = $derived(transaction ? transaction?.category?.id ?? '' : (categories[0]?.id ?? ''));
    const dialogTitle = $derived(
        isEditing
            ? type === 'expense'
                ? 'تعديل مصروف'
                : 'تعديل دخل'
            : type === 'expense'
              ? 'إضافة مصروف'
              : 'إضافة دخل',
    );
    const submitLabel = $derived(isEditing ? 'حفظ التعديلات' : 'إضافة');

    let formKey = $state(0);
    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            formKey += 1;
        }
        wasOpen = open;
    });

    function handleOpenChange(value: boolean) {
        open = value;
        onOpenChange?.(value);
    }
</script>

<Dialog {open} onOpenChange={handleOpenChange}>
    <DialogContent class="sm:max-w-md">
        {#key `${transaction?.id ?? 'new'}:${formKey}`}
            <DialogHeader>
                <DialogTitle>{dialogTitle}</DialogTitle>
                <DialogDescription>
                    {type === 'expense' ? 'سجّل تفاصيل المصروف' : 'سجّل تفاصيل الدخل'}
                </DialogDescription>
            </DialogHeader>

            {#if isEditing && transaction}
                <Form
                    {...TransactionController.update.form(transaction.id)}
                    class="space-y-4"
                    options={{ preserveScroll: true }}
                    onSuccess={() => (open = false)}
                >
                    {#snippet children({ errors, processing })}
                        {@render fields({ errors, processing, submitLabel })}
                    {/snippet}
                </Form>
            {:else}
                <Form
                    {...TransactionController.store.form()}
                    class="space-y-4"
                    options={{ preserveScroll: true }}
                    onSuccess={() => (open = false)}
                >
                    {#snippet children({ errors, processing })}
                        {@render fields({ errors, processing, submitLabel })}
                    {/snippet}
                </Form>
            {/if}
        {/key}
    </DialogContent>
</Dialog>

{#snippet fields({ errors, processing, submitLabel }: { errors: Record<string, string>; processing: boolean; submitLabel: string })}
    <div class="grid gap-2">
        <Label for="transaction-description">الوصف</Label>
        <Input
            id="transaction-description"
            name="description"
            value={transaction?.description ?? ''}
            placeholder="وصف المعاملة"
            required
        />
        <InputError message={errors.description} />
    </div>

    <div class="grid gap-2">
        <Label for="transaction-amount">المبلغ</Label>
        <Input
            id="transaction-amount"
            name="amount"
            type="number"
            step="0.01"
            min="0.01"
            value={transaction?.amount ?? ''}
            placeholder="0.00"
            required
        />
        <InputError message={errors.amount} />
    </div>

    <div class="grid gap-2">
        <Label for="transaction-date">التاريخ</Label>
        <Input id="transaction-date" name="date" type="date" value={defaultDate} required />
        <InputError message={errors.date} />
    </div>

    <div class="grid gap-2">
        <Label for="transaction-category">التصنيف</Label>
        <NativeSelect id="transaction-category" name="category_id" value={defaultCategoryId} class="w-full">
            {#each categories as cat}
                <NativeSelectOption value={cat.id}>{cat.name}</NativeSelectOption>
            {/each}
        </NativeSelect>
        <InputError message={errors.category_id} />
    </div>

    <div class="grid gap-2">
        <Label for="transaction-vendor">التاجر (اختياري)</Label>
        <Input
            id="transaction-vendor"
            name="vendor"
            value={transaction?.vendor ?? ''}
            placeholder="اسم التاجر أو الجهة"
        />
        <InputError message={errors.vendor} />
    </div>

    <input type="hidden" name="type" value={type} />

    <DialogFooter class="gap-2">
        <Button type="button" variant="outline" onclick={() => (open = false)}>إلغاء</Button>
        <Button type="submit" disabled={processing}>{submitLabel}</Button>
    </DialogFooter>
{/snippet}
