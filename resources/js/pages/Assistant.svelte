<script module lang="ts">
    import { assistant } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'المساعد الذكي', href: assistant() }],
    };
</script>

<script lang="ts">
    import Bot from 'lucide-svelte/icons/bot';
    import Check from 'lucide-svelte/icons/check';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import CircleX from 'lucide-svelte/icons/circle-x';
    import LoaderCircle from 'lucide-svelte/icons/loader-circle';
    import Plus from 'lucide-svelte/icons/plus';
    import Send from 'lucide-svelte/icons/send';
    import Square from 'lucide-svelte/icons/square';
    import User from 'lucide-svelte/icons/user';
    import { onMount } from 'svelte';
    import AssistantController from '@/actions/App/Http/Controllers/AssistantController';
    import AppHead from '@/components/AppHead.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';

    type ChatRole = 'user' | 'assistant';

    type ToolCallMessage = {
        kind: 'tool_call';
        uid: string;
        id: string;
        name: string;
        arguments: Record<string, unknown>;
        status: 'running' | 'success' | 'error';
        summary: string;
    };

    type TextMessage = {
        kind: 'text';
        uid: string;
        role: ChatRole;
        content: string;
    };

    type Message = TextMessage | ToolCallMessage;

    type StreamEvent =
        | { type: 'text'; delta: string }
        | { type: 'tool_call'; id: string; name: string; arguments: Record<string, unknown> }
        | { type: 'tool_result'; id: string; name: string; summary: string; ok: boolean }
        | { type: 'error'; message: string }
        | { type: 'done' };

    const toolLabels: Record<string, string> = {
        ListTransactions: 'عرض العمليات',
        CreateTransactions: 'إضافة عمليات',
        UpdateTransactions: 'تعديل عمليات',
        DeleteTransactions: 'حذف عمليات',
    };

    const examples = [
        'كم صرفت هذا الشهر؟',
        'أضف مصروف ٥٠ ريال قهوة أمس',
        'اعرض أكبر ٥ مصروفات',
        'احذف آخر عملية',
    ];

    let messages = $state<Message[]>([
        {
            kind: 'text',
            uid: 'welcome',
            role: 'assistant',
            content: 'مرحباً! أنا مساعد كاش سينس الذكي. اسألني عن مصروفاتك، دخلك، أو اطلب مني إضافة أو تعديل أو حذف العمليات.',
        },
    ]);
    let inputText = $state('');
    let isStreaming = $state(false);
    let chatContainer: HTMLDivElement | undefined = $state();

    let controller: AbortController | undefined = $state();

    onMount(() => {
        scrollToBottom();
    });

    function scrollToBottom() {
        requestAnimationFrame(() => {
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    }

    function newChat() {
        if (isStreaming) {
            return;
        }

        messages = [
            {
                kind: 'text',
                uid: crypto.randomUUID(),
                role: 'assistant',
                content: 'مرحباً! أنا مساعد كاش سينس الذكي. اسألني عن مصروفاتك، دخلك، أو اطلب مني إضافة أو تعديل أو حذف العمليات.',
            },
        ];
    }

    function conversationHistory(): { role: ChatRole; content: string }[] {
        return messages
            .filter((m): m is TextMessage => m.kind === 'text')
            .slice(-20)
            .map((m) => ({ role: m.role, content: m.content }));
    }

    function appendDelta(delta: string) {
        const idx = messages.findLastIndex((m) => m.kind === 'text' && m.role === 'assistant');

        if (idx === -1) {
            messages = [...messages, { kind: 'text', uid: crypto.randomUUID(), role: 'assistant', content: delta }];
        } else {
            const target = messages[idx];
            const next = [...messages];
            next[idx] =
                target.kind === 'text'
                    ? { kind: 'text', uid: target.uid, role: 'assistant', content: target.content + delta }
                    : target;
            messages = next;
        }
    }

    function findToolIndex(id: string): number {
        return messages.findIndex((m) => m.kind === 'tool_call' && m.id === id);
    }

    async function sendMessage(text?: string) {
        const content = (text ?? inputText).trim();

        if (!content || isStreaming) {
            return;
        }

        inputText = '';
        messages = [...messages, { kind: 'text', uid: crypto.randomUUID(), role: 'user', content }];
        isStreaming = true;
        scrollToBottom();

        controller = new AbortController();
        const timeout = setTimeout(() => controller?.abort(), 150_000);

        try {
            const history = conversationHistory();

            const response = await fetch(AssistantController.stream().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-XSRF-TOKEN': readXsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
                body: JSON.stringify({ message: content, history }),
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            if (!response.body) {
                throw new Error('Streaming not supported by the browser.');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                let boundary = buffer.indexOf('\n\n');

                while (boundary !== -1) {
                    const raw = buffer.slice(0, boundary);
                    buffer = buffer.slice(boundary + 2);
                    handleFrame(raw);
                    boundary = buffer.indexOf('\n\n');
                }
            }
        } catch (err) {
            const aborted = err instanceof DOMException && err.name === 'AbortError';
            messages = [
                ...messages,
                {
                    kind: 'text',
                    uid: crypto.randomUUID(),
                    role: 'assistant',
                    content: aborted
                        ? 'تم إيقاف الرد.'
                        : 'تعذّر الاتصال بالمساعد. تحقق من اتصالك وحاول مرة أخرى.',
                },
            ];
        } finally {
            clearTimeout(timeout);
            isStreaming = false;
            controller = undefined;
            scrollToBottom();
        }
    }

    function handleFrame(raw: string) {
        if (!raw.startsWith('data: ')) {
            return;
        }

        const payload = raw.slice(6).trim();

        if (!payload) {
            return;
        }

        let event: StreamEvent;

        try {
            event = JSON.parse(payload) as StreamEvent;
        } catch {
            return;
        }

        switch (event.type) {
            case 'text':
                appendDelta(event.delta);
                scrollToBottom();
                break;
            case 'tool_call':
                messages = [
                    ...messages,
                    {
                        kind: 'tool_call',
                        uid: crypto.randomUUID(),
                        id: event.id,
                        name: event.name,
                        arguments: event.arguments,
                        status: 'running',
                        summary: 'جارٍ التنفيذ…',
                    },
                ];
                scrollToBottom();
                break;
            case 'tool_result': {
                const idx = findToolIndex(event.id);

                if (idx !== -1) {
                    const next = [...messages];
                    next[idx] = {
                        ...(next[idx] as ToolCallMessage),
                        status: event.ok ? 'success' : 'error',
                        summary: event.summary || (event.ok ? 'اكتمل التنفيذ.' : 'فشل التنفيذ.'),
                    };
                    messages = next;
                }

                scrollToBottom();
                break;
            }
            case 'error':
                messages = [
                    ...messages,
                    { kind: 'text', uid: crypto.randomUUID(), role: 'assistant', content: event.message },
                ];
                scrollToBottom();
                break;
            case 'done':
                break;
        }
    }

    function stopStreaming() {
        controller?.abort();
    }

    function handleKeydown(e: KeyboardEvent) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function readXsrfToken(): string {
        const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));

        if (!cookie) {
            return '';
        }

        try {
            return decodeURIComponent(cookie.split('=').slice(1).join('='));
        } catch {
            return cookie.split('=').slice(1).join('=');
        }
    }

    function formatArguments(args: Record<string, unknown>): string {
        try {
            return JSON.stringify(args, null, 2);
        } catch {
            return String(args);
        }
    }

    let openToolCards = $state<Record<string, boolean>>({});
</script>

<AppHead title="المساعد الذكي" />

<div class="flex h-full flex-1 flex-col" dir="rtl">
    <div class="flex items-center justify-between border-b border-border bg-card px-4 py-3">
        <div class="flex items-center gap-2">
            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                <Bot class="size-4 text-primary" />
            </div>
            <div>
                <h1 class="text-sm font-bold">المساعد الذكي</h1>
                <p class="text-xs text-muted-foreground">أسألني عن مصروفاتك أو أطلب مني إدارة عملياتك المالية</p>
            </div>
        </div>
        <Button variant="ghost" size="sm" onclick={newChat} disabled={isStreaming}>
            <Plus class="size-4 ml-2" />
            محادثة جديدة
        </Button>
    </div>

    <div
        bind:this={chatContainer}
        aria-live="polite"
        class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
    >
        {#each messages as msg (msg.uid)}
            {#if msg.kind === 'text'}
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
                    <div
                        class="max-w-[80%] whitespace-pre-line rounded-2xl px-4 py-2.5 text-sm leading-relaxed {msg.role === 'user'
                            ? 'bg-primary text-primary-foreground rounded-br-sm'
                            : 'bg-secondary text-foreground rounded-bl-sm'}"
                    >
                        {msg.content}
                    </div>
                </div>
            {:else}
                <div class="flex items-start gap-3">
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback class="bg-primary text-primary-foreground text-xs">
                            <Bot class="size-4" />
                        </AvatarFallback>
                    </Avatar>
                    <div class="w-full max-w-[85%] rounded-2xl border border-border bg-card px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                {#if msg.status === 'running'}
                                    <LoaderCircle class="size-4 animate-spin text-primary" />
                                {:else if msg.status === 'success'}
                                    <Check class="size-4 text-green-600 dark:text-green-400" />
                                {:else}
                                    <CircleX class="size-4 text-destructive" />
                                {/if}
                                <span class="text-sm font-semibold">{toolLabels[msg.name] ?? msg.name}</span>
                            </div>
                            <button
                                type="button"
                                onclick={() => (openToolCards[msg.id] = !openToolCards[msg.id])}
                                class="flex items-center gap-1 rounded-md px-2 py-1 text-xs text-muted-foreground transition-colors hover:bg-secondary"
                                aria-expanded={openToolCards[msg.id] === true}
                            >
                                التفاصيل
                                <ChevronDown class="size-3.5 transition-transform {openToolCards[msg.id] ? 'rotate-180' : ''}" />
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">{msg.summary}</p>
                        {#if openToolCards[msg.id]}
                            <pre class="mt-2 max-h-64 overflow-auto rounded-lg bg-secondary p-3 text-xs text-foreground">{formatArguments(msg.arguments)}</pre>
                        {/if}
                    </div>
                </div>
            {/if}
        {/each}

        {#if messages.length === 1}
            <div class="flex flex-col items-center justify-center gap-4 py-10">
                <Bot class="size-10 text-primary/60" />
                <p class="text-sm text-muted-foreground">جرّب أحد هذه الأسئلة:</p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    {#each examples as example (example)}
                        <button
                            type="button"
                            onclick={() => sendMessage(example)}
                            class="rounded-full border border-border bg-card px-4 py-2 text-sm text-foreground transition-colors hover:bg-secondary"
                        >
                            {example}
                        </button>
                    {/each}
                </div>
            </div>
        {/if}
    </div>

    <div class="border-t border-border bg-card px-4 py-3">
        <form
            class="flex items-end gap-2"
            onsubmit={(e) => {
                e.preventDefault();
                sendMessage();
            }}
        >
            <textarea
                bind:value={inputText}
                placeholder="اسأل المساعد الذكي..."
                class="flex-1 resize-none rounded-xl border border-border bg-secondary px-3 py-2 text-sm text-foreground outline-none transition-colors placeholder:text-muted-foreground focus:border-primary"
                rows="1"
                onkeydown={handleKeydown}
                disabled={isStreaming}
            ></textarea>
            {#if isStreaming}
                <Button type="button" size="icon" class="shrink-0" variant="outline" onclick={stopStreaming} title="إيقاف">
                    <Square class="size-4" />
                </Button>
            {:else}
                <Button type="submit" size="icon" class="shrink-0" disabled={!inputText.trim()}>
                    <Send class="size-4" />
                </Button>
            {/if}
        </form>
        <p class="mt-2 text-center text-xs text-muted-foreground">Enter للإرسال · Shift+Enter لسطر جديد</p>
    </div>
</div>
