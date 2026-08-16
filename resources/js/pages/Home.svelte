<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Badge } from '@/components/ui/badge';
    import { toUrl } from '@/lib/utils';
    import { register, dashboard, pricing } from '@/routes';

    const auth = $derived(page.props.auth);

    const features = [
        {
            title: 'تصنيف تلقائي بالذكاء الاصطناعي',
            desc: 'يكتب النظام المصروفات تلقائياً في المكان الصحيح. بدون تعب ولا أخطاء.',
        },
        {
            title: 'تصوير الفواتير',
            desc: 'صور الفاتورة وخل الباقي علينا. نقرى المبلغ والتاريخ والتاجر تلقائياً.',
        },
        {
            title: 'تقارير ذكية',
            desc: 'شوف وين تروح فلوسك برسومات بيانية بسيطة. اكتشف طرق للتوفير.',
        },
        {
            title: 'حسابات مشتركة',
            desc: 'شارك المصروفات مع فريقك أو عائلتك. الكل يشوف وين تروح الفلوس.',
        },
        {
            title: 'تنبيهات الميزانية',
            desc: 'ننبّهك لما تقرب من حد الميزانية. ولا تنسيك فواتيرك أبداً.',
        },
        {
            title: 'تقارير للتصدير',
            desc: 'صدر تقاريرك بصيغة PDF أو Excel أو CSV. شاركها مع محاسبك بضغطة زر.',
        },
    ];
</script>

<AppHead title="كاش سينس - تتبع المصروفات بالذكاء الاصطناعي" />

<section class="px-6 pb-24 pt-20 md:pb-32 md:pt-28">
    <div class="mx-auto max-w-3xl text-center">
        <Badge variant="outline" class="mb-6">تتبع المصروفات بالذكاء الاصطناعي</Badge>
        <h1 class="text-4xl font-bold tracking-tight text-foreground md:text-5xl lg:text-6xl">
            اعرف وين تروح فلوسك
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">
            كاش سينس يصنف مصروفاتك تلقائياً بالذكاء الاصطناعي.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            {#if auth.user}
                <Link href={toUrl(dashboard())}>
                    <Button size="lg" class="px-8 text-base">افتح لوحة التحكم</Button>
                </Link>
            {:else}
                <Link href={toUrl(register())}>
                    <Button size="lg" class="px-8 text-base">ابدأ مجاناً</Button>
                </Link>
                <Link href={toUrl(pricing())}>
                    <Button variant="outline" size="lg" class="px-8 text-base">شوف الأسعار</Button>
                </Link>
            {/if}
        </div>
    </div>
</section>

<section class="border-t border-border bg-card px-6 py-16 md:py-24">
    <div class="mx-auto max-w-5xl">
        <div class="mx-auto mb-12 max-w-xl text-center">
            <h2 class="text-2xl font-bold md:text-3xl">كل اللي تحتاجه لإدارة مصروفاتك</h2>
            <p class="mt-2 text-muted-foreground">من التصنيف الذكي إلى التقارير التحليلة، كاش سينس يشتغل نيابة عنك.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {#each features as feature}
                <Card class="border border-border bg-card shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">{feature.title}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <CardDescription class="text-sm leading-relaxed">{feature.desc}</CardDescription>
                    </CardContent>
                </Card>
            {/each}
        </div>
    </div>
</section>

<section class="bg-primary px-6 py-16 text-center text-primary-foreground md:py-24">
    <div class="mx-auto max-w-2xl">
        <h2 class="text-2xl font-bold md:text-3xl">جهّز تتحكم في فلوسك؟</h2>
        <p class="mt-2 text-primary-foreground/80">انضم لآلاف الشركات الصغيرة اللي تستخدم كاش سينس لتتبع وتحليل مصروفاتها.</p>
        <div class="mt-8 flex items-center justify-center gap-3">
            {#if auth.user}
                <Link href={toUrl(dashboard())}>
                    <Button size="lg" variant="secondary" class="px-8 text-base">افتح لوحة التحكم</Button>
                </Link>
            {:else}
                <Link href={toUrl(register())}>
                    <Button size="lg" variant="secondary" class="px-8 text-base">ابدأ مجاناً</Button>
                </Link>
            {/if}
        </div>
    </div>
</section>