<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Badge } from '@/components/ui/badge';
    import { Input } from '@/components/ui/input';
    import Search from 'lucide-svelte/icons/search';
    import BookOpen from 'lucide-svelte/icons/book-open';
    import Camera from 'lucide-svelte/icons/camera';
    import Users from 'lucide-svelte/icons/users';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import Settings from 'lucide-svelte/icons/settings';
    import Upload from 'lucide-svelte/icons/upload';
    import Bell from 'lucide-svelte/icons/bell';

    const sections = [
        {
            icon: BookOpen,
            title: 'بداية سريعة',
            desc: 'أنشئ حسابك، أضف فريقك، واربط أول مصروفاتك.',
            articles: ['إنشاء الحساب', 'نظرة على لوحة التحكم', 'إضافة أول مصروف', 'دعوة أعضاء الفريق'],
        },
        {
            icon: Camera,
            title: 'تصوير الفواتير',
            desc: 'صور الفاتورة وخل الذكاء الاصطناعي يقراها بدالك.',
            articles: ['تصوير فاتورة', 'مراجعة البيانات المستخرجة', 'نصائح التصوير', 'شروط الصورة المقبولة'],
        },
        {
            icon: BarChart3,
            title: 'التقارير والتحليلات',
            desc: 'افهم وين تروح فلوسك من خلال تقارير بصرية بسيطة.',
            articles: ['التقارير العامة', 'تحليل الفئات', 'تحليل الاتجاهات', 'تصدير التقارير'],
        },
        {
            icon: Users,
            title: 'الحسابات المشتركة',
            desc: 'تعاون مع فريقك في المصروفات والميزانيات المشتركة.',
            articles: ['إنشاء حساب مشترك', 'إدارة الصلاحيات', 'تقسيم المصروفات', 'سجل النشاطات'],
        },
        {
            icon: Settings,
            title: 'الإعدادات والتفضيلات',
            desc: 'خصص الفئات، العملات، وإشعاراتك.',
            articles: ['فئات مخصصة', 'إعدادات العملة', 'تفضيلات الإشعارات', 'أمان الحساب'],
        },
        {
            icon: Upload,
            title: 'استيراد وتصدير',
            desc: 'استورد بياناتك الحالية أو صدر تقاريرك بصيغ متعددة.',
            articles: ['استيراد من CSV', 'استيراد من كشف بنك', 'تصدير CSV/PDF/Excel', 'نسخ احتياطي'],
        },
        {
            icon: Bell,
            title: 'الإشعارات والتنبيهات',
            desc: 'ابق على اطلاع بالفواتير والميزانيات والنشاطات غير المعتادة.',
            articles: ['إعداد تنبيهات الميزانية', 'تذكير الفواتير', 'تنبيهات الأنشطة غير المعتادة', 'قنوات الإشعارات'],
        },
    ];

    const faqs = [
        { q: 'هل بياناتي المالية آمنة؟', a: 'نعم. كل البيانات مشفرة. نستخدم تشفير AES-256 ولا نشارك بياناتك مع أي طرف ثالث.' },
        { q: 'أقدر استخدم كاش سينس على الجوال؟', a: 'كاش سينس يشتغل على كل الأجهزة через المتصفح. تطبيقات الجوال قريباً.' },
        { q: 'كيف يشتغل التصنيف بالذكاء الاصطناعي؟', a: 'النظام يتعلم من تصنيفاتك السابقة. يصنف تلقائياً المصروفات الجديدة حسب التاجر والمبلغ.' },
        { q: 'وش يصير لو تعديت حد الباقة؟', a: 'ننبّهك لمّا تقرب من الحد. تقدر ترقي بقتك في أي وقت بدون فقدان بيانات.' },
    ];
</script>

<AppHead title="المساعدة" />

<section class="px-4 py-16 md:py-24" dir="rtl">
    <div class="mx-auto max-w-5xl">
        <div class="mx-auto mb-10 max-w-xl text-center">
            <Badge variant="outline" class="mb-3">مركز المساعدة</Badge>
            <h1 class="text-3xl font-bold md:text-4xl">كيف نقدر نساعدك؟</h1>
            <p class="mt-2 text-muted-foreground">ابحث في المقالات والأدلة والأسئلة المتكررة.</p>
            <div class="relative mx-auto mt-6 max-w-sm">
                <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input placeholder="ابحث في المقالات..." class="pr-10 text-right" />
            </div>
        </div>

        <div class="mb-16 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {#each sections as section}
                <Card class="border border-border bg-card shadow-sm">
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                                <section.icon class="size-4 text-primary" />
                            </div>
                            <CardTitle class="text-base">{section.title}</CardTitle>
                        </div>
                        <CardDescription class="mt-1 text-sm">{section.desc}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-1">
                            {#each section.articles as article}
                                <li>
                                    <a href="/docs" class="text-sm text-primary transition-colors hover:text-primary/80 hover:underline">{article}</a>
                                </li>
                            {/each}
                        </ul>
                    </CardContent>
                </Card>
            {/each}
        </div>

        <div class="border-t border-border pt-12">
            <h2 class="mb-6 text-xl font-bold text-center">أسئلة متكررة</h2>
            <div class="mx-auto max-w-2xl space-y-3">
                {#each faqs as faq}
                    <details class="group rounded-lg border border-border p-4 transition-shadow open:shadow-sm">
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-sm">
                            {faq.q}
                            <span class="text-muted-foreground transition-transform group-open:rotate-180">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </summary>
                        <p class="mt-2 text-sm text-muted-foreground">{faq.a}</p>
                    </details>
                {/each}
            </div>
        </div>

        <div class="mt-12 rounded-lg bg-secondary p-6 text-center">
            <h3 class="font-bold">لسه محتاج مساعدة؟</h3>
            <p class="mt-1 text-sm text-muted-foreground">فريق الدعم الفني موجود لخدمتك.</p>
            <a href="/contact" class="mt-3 inline-flex items-center text-sm font-medium text-primary hover:underline">اتصل بنا</a>
        </div>
    </div>
</section>