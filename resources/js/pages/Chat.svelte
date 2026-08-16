<script module lang="ts">
    import { chat } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'المساعد الذكي',
                href: chat(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import Send from 'lucide-svelte/icons/send';
    import Bot from 'lucide-svelte/icons/bot';
    import User from 'lucide-svelte/icons/user';

    type Message = {
        role: 'user' | 'assistant';
        text: string;
    };

    let messages: Message[] = $state([
        { role: 'assistant', text: 'مرحباً! أنا مساعد كاش سينس الذكي. أسألني أي سؤال عن مصروفاتك، ميزانيتك، أو تقاريرك المالية.' },
    ]);
    let inputText = $state('');
    let isTyping = $state(false);
    let chatContainer: HTMLDivElement | undefined = $state();

    function scrollToBottom() {
        requestAnimationFrame(() => {
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    }

    function getBotResponse(userMessage: string): string {
        const msg = userMessage.toLowerCase();

        if (msg.includes('مصروف') || msg.includes('صرفت') || msg.includes('كم صرفت')) {
            return 'مصروفاتك هذا الشهر total $4,230. أعلى فئة هي البرمجيات ($1,480). تقدر توفّر $320 لو راجعت اشتراكاتك الشهرية.';
        }
        if (msg.includes('دخل') || msg.includes('كم كسبت') || msg.includes('راتب')) {
            return 'دخلك هذا الشهر $8,950. أعلى مصدر دخل هو العمل الحر ($3,200). أحسنت!';
        }
        if (msg.includes('ميزانية') || msg.includes('باقي') || msg.includes('متبقي')) {
            return 'عندك ميزانية شهرية $7,000. صرفت $4,230 (%60). باقي $2,770. التزم بميزانية الوجبات عشان تكمل الشهر.';
        }
        if (msg.includes('توفير') || msg.includes('أوفر') || msg.includes('اقتصاد')) {
            return 'بناءً على تحليل مصروفاتك، أقترح:\n١- راجع اشتراكات البرمجيات (متوسط $1,480/شهر)\n٢- قلل مصروفات الوجبات (%18 من الإجمالي)\n٣- استخدم خاصية تصوير الفواتير عشان ما يفوتك شيء.';
        }
        if (msg.includes('مرحب') || msg.includes('السلام') || msg.includes('هلا') || msg.includes('مساء') || msg.includes('صباح')) {
            return 'وعليكم السلام! كيف أقدر أساعدك اليوم؟ أسألني عن مصروفاتك، دخلك، ميزانيتك، أو أي استفسار مالي.';
        }

        return 'فهمت سؤالك. حالياً أقدر أساعدك في:\n• استعراض مصروفاتك وتحليلها\n• مراجعة دخلك الشهري\n• متابعة الميزانية\n• اقتراحات للتوفير\n\nتقدر تسألني مثلاً: "كم صرفت هذا الشهر؟" أو "وش باقي من الميزانية؟"';
    }

    function sendMessage() {
        const text = inputText.trim();
        if (!text || isTyping) return;

        inputText = '';
        messages = [...messages, { role: 'user', text }];
        isTyping = true;
        scrollToBottom();

        setTimeout(() => {
            const reply = getBotResponse(text);
            messages = [...messages, { role: 'assistant', text: reply }];
            isTyping = false;
            scrollToBottom();
        }, 800);
    }

    function handleKeydown(e: KeyboardEvent) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }
</script>

<AppHead title="المساعد الذكي" />

<div class="flex h-full flex-1 flex-col" dir="rtl">
    <div class="border-b border-border bg-card px-4 py-3">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                <Bot class="size-4 text-primary" />
            </div>
            <div>
                <h1 class="text-sm font-bold">المساعد الذكي</h1>
                <p class="text-xs text-muted-foreground">أسألني عن مصروفاتك وتحليلاتك المالية</p>
            </div>
        </div>
    </div>

    <div
        bind:this={chatContainer}
        class="flex-1 overflow-y-auto px-4 py-4 space-y-4"
    >
        {#each messages as msg, i}
            <div class="flex items-start gap-3 {msg.role === 'user' ? 'flex-row-reverse' : ''}">
                {#if msg.role === 'assistant'}
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback class="bg-primary text-primary-foreground text-xs">AI</AvatarFallback>
                    </Avatar>
                {:else}
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback class="bg-secondary text-foreground text-xs">
                            <User class="size-4" />
                        </AvatarFallback>
                    </Avatar>
                {/if}
                <div class="max-w-[80%] {msg.role === 'user' ? 'bg-primary text-primary-foreground rounded-2xl rounded-br-sm' : 'bg-secondary text-foreground rounded-2xl rounded-bl-sm'} px-4 py-2.5 text-sm leading-relaxed whitespace-pre-line">
                    {msg.text}
                </div>
            </div>
        {/each}
        {#if isTyping}
            <div class="flex items-start gap-3">
                <Avatar class="size-8 shrink-0">
                    <AvatarFallback class="bg-primary text-primary-foreground text-xs">AI</AvatarFallback>
                </Avatar>
                <div class="bg-secondary text-foreground rounded-2xl rounded-bl-sm px-4 py-3">
                    <div class="flex gap-1">
                        <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 0ms"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 150ms"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>
        {/if}
    </div>

    <div class="border-t border-border bg-card px-4 py-3">
        <form class="flex items-center gap-2" onsubmit={(e) => { e.preventDefault(); sendMessage(); }}>
            <Input
                bind:value={inputText}
                placeholder="اسأل المساعد الذكي..."
                class="flex-1"
                onkeydown={handleKeydown}
                disabled={isTyping}
            />
            <Button type="submit" size="icon" class="shrink-0" disabled={!inputText.trim() || isTyping}>
                <Send class="size-4" />
            </Button>
        </form>
    </div>
</div>