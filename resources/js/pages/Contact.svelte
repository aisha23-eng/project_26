<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Textarea } from '@/components/ui/textarea';
    import { Label } from '@/components/ui/label';
    import { Badge } from '@/components/ui/badge';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import Mail from 'lucide-svelte/icons/mail';
    import MessageCircle from 'lucide-svelte/icons/message-circle';
    import Twitter from 'lucide-svelte/icons/twitter';
</script>

<AppHead title="اتصل بنا" />

<section class="px-4 py-16 md:py-24" dir="rtl">
    <div class="mx-auto max-w-5xl">
        <div class="mx-auto mb-12 max-w-xl text-center">
            <Badge variant="outline" class="mb-3">اتصل بنا</Badge>
            <h1 class="text-3xl font-bold md:text-4xl">تواصل معنا</h1>
            <p class="mt-2 text-muted-foreground">عندك سؤال أو اقتراح؟ نحب نسمع منك.</p>
        </div>
        <div class="grid gap-8 md:grid-cols-2">
            <div>
                <form class="space-y-4" method="POST" action="/contact">
                    <div class="space-y-1.5">
                        <Label for="name">الاسم</Label>
                        <Input id="name" name="name" placeholder="اسمك" required />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="email">البريد الإلكتروني</Label>
                        <Input id="email" name="email" type="email" placeholder="you@example.com" required />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="subject">الموضوع</Label>
                        <Input id="subject" name="subject" placeholder="كيف نقدر نساعدك؟" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="message">الرسالة</Label>
                        <Textarea id="message" name="message" placeholder="اشرح لنا أكثر..." rows={5} required />
                    </div>
                    <Button type="submit" class="w-full">إرسال</Button>
                </form>
            </div>
            <div class="space-y-4">
                <Card class="border border-border shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Mail class="size-4 text-primary" />
                            البريد الإلكتروني
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">فريقنا يرد خلال ٢٤ ساعة.</p>
                        <a href="mailto:support@cashsense.app" class="mt-1 block text-sm font-medium text-primary hover:underline">support@cashsense.app</a>
                    </CardContent>
                </Card>
                <Card class="border border-border shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <MessageCircle class="size-4 text-primary" />
                            المحادثة المباشرة
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">متاح من الأحد للخميس، ٩ صباحاً - ٦ مساءً.</p>
                        <Button variant="link" class="mt-1 h-auto p-0 text-sm font-medium text-primary">ابدأ محادثة</Button>
                    </CardContent>
                </Card>
                <Card class="border border-border shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Twitter class="size-4 text-primary" />
                            وسائل التواصل
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">تابعنا للآخبار والنصائح.</p>
                        <span class="mt-1 block text-sm font-medium text-primary">@cashsense</span>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</section>