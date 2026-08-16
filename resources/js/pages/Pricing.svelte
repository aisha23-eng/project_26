<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
    import { Badge } from '@/components/ui/badge';
    import { toUrl } from '@/lib/utils';
    import { register } from '@/routes';
    import Check from 'lucide-svelte/icons/check';

    const plans = [
        {
            name: 'بداية',
            price: '٠',
            period: 'مجاناً للأبد',
            desc: 'مناسب للأفراد والمستقلين.',
            features: [
                'حتى ٥٠ معاملة في الشهر',
                'تصنيف تلقائي بالذكاء الاصطناعي',
                'تصوير فواتير (١٠ في الشهر)',
                'لوحة تحكم بسيطة',
                'تصدير CSV',
            ],
            cta: 'ابدأ مجاناً',
            href: register(),
            highlighted: false,
        },
        {
            name: 'احترافي',
            price: '١٢',
            period: 'دولار/الشهر',
            desc: 'للفرق الصغيرة اللي تحتاج إدارة محترفة.',
            features: [
                'معاملات غير محدودة',
                'تصنيف ذكي متطور',
                'تصوير فواتير غير محدود',
                'ميزانيات وتوقعات ذكية',
                'حسابات مشتركة (حتى ٥)',
                'دعم عملات متعددة',
                'تصدير PDF و Excel',
                'دعم أولوي',
            ],
            cta: 'جرب مجاناً ١٤ يوم',
            href: register(),
            highlighted: true,
        },
        {
            name: 'شركات',
            price: '٢٩',
            period: 'دولار/الشهر',
            desc: 'للشركات المتنامية اللي تحتاج حلول متقدمة.',
            features: [
                'كل ميزات الاحترافي',
                'حسابات مشتركة غير محدودة',
                'ربط بالبنوك تلقائياً',
                'فئات وقواعد مخصصة',
                'API للوصول البرمجي',
                'مدير حساب مخصص',
                'SSO وأمان متقدم',
                'تقارير مخصصة',
            ],
            cta: 'اتصل بنا',
            href: '/contact',
            highlighted: false,
        },
    ];
</script>

<section class="px-4 py-16 md:py-24" dir="rtl">
    <div class="mx-auto max-w-5xl">
        <div class="mx-auto mb-12 max-w-xl text-center">
            <Badge variant="outline" class="mb-3">الأسعار</Badge>
            <h1 class="text-3xl font-bold md:text-4xl">أسعار واضحة ومباشرة</h1>
            <p class="mt-2 text-muted-foreground">بدون رسوم خفية. وقف أو ارتقى في أي وقت.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            {#each plans as plan}
                <Card class="relative flex flex-col border {plan.highlighted ? 'border-primary ring-1 ring-primary shadow-md' : 'border-border shadow-sm'}">
                    {#if plan.highlighted}
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <Badge class="bg-primary text-primary-foreground hover:bg-primary">الأكثر طلباً</Badge>
                        </div>
                    {/if}
                    <CardHeader>
                        <CardTitle class="text-lg">{plan.name}</CardTitle>
                        <div class="mt-1">
                            <span class="text-3xl font-bold">{plan.price}</span>
                            <span class="mr-1 text-sm text-muted-foreground">{plan.period}</span>
                        </div>
                        <CardDescription class="mt-1">{plan.desc}</CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1">
                        <ul class="space-y-2">
                            {#each plan.features as feature}
                                <li class="flex items-center gap-2 text-sm">
                                    <Check class="size-4 shrink-0 text-primary" />
                                    <span>{feature}</span>
                                </li>
                            {/each}
                        </ul>
                    </CardContent>
                    <CardFooter>
                        <Link href={toUrl(plan.href)} class="w-full">
                            <Button variant={plan.highlighted ? 'default' : 'outline'} class="w-full">
                                {plan.cta}
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>
            {/each}
        </div>
    </div>
</section>

<section class="border-t border-border bg-card px-4 py-16" dir="rtl">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-xl font-bold md:text-2xl">أسئلة متكررة</h2>
        <div class="mt-8 grid gap-4 text-right md:grid-cols-2">
            <div>
                <h3 class="mb-1 font-bold text-sm">أقدر أغير الباقة في أي وقت؟</h3>
                <p class="text-sm text-muted-foreground">أكيد. ترقية أو تخفيض في أي وقت. التغييرات تطبق فوراً.</p>
            </div>
            <div>
                <h3 class="mb-1 font-bold text-sm">فيه فترة تجربة مجانية؟</h3>
                <p class="text-sm text-muted-foreground">نعم. جرب الباقة الاحترافية مجاناً ١٤ يوم. بدون بطاقة ائتمان.</p>
            </div>
            <div>
                <h3 class="mb-1 font-bold text-sm">وش طرق الدفع؟</h3>
                <p class="text-sm text-muted-foreground">بطاقات ائتمان، باي بال، وتحويل بنكي للاشتراكات السنوية.</p>
            </div>
            <div>
                <h3 class="mb-1 font-bold text-sm">أقدر ألغي اشتراكي؟</h3>
                <p class="text-sm text-muted-foreground">طبعاً. إلغاء بضغطة زر. بياناتك تبقى محفوظة ٣٠ يوم.</p>
            </div>
        </div>
    </div>
</section>