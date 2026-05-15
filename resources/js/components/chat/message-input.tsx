import { SendHorizontal } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { getCsrfToken } from '@/lib/api';
import { store } from '@/routes/messages';
import type { Message } from '@/types/conversations';

interface MessageInputProps {
    conversationId: number;
    onUserMessage: (userMessage: Message, title: string | null) => void;
    onChunk: (chunk: string) => void;
    onAssistantMessage: (assistantMessage: Message) => void;
    isLocked?: boolean;
}

export default function MessageInput({
    conversationId,
    onUserMessage,
    onChunk,
    onAssistantMessage,
    isLocked = false,
}: MessageInputProps) {
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function handleSubmit(e: React.SyntheticEvent) {
        e.preventDefault();

        if (!content.trim() || loading) {
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(store(conversationId).url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ content }),
            });

            if (!response.ok || !response.body) {
                setError('Something went wrong. Please try again.');

                return;
            }

            setContent('');

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                const lines = buffer.split('\n');

                buffer = lines.pop() ?? '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) {
                        continue;
                    }

                    const event = JSON.parse(line.slice(6)) as {
                        type: string;
                        message?: Message;
                        title?: string | null;
                        content?: string;
                    };

                    if (event.type === 'user' && event.message) {
                        onUserMessage(event.message, event.title ?? null);
                    } else if (event.type === 'chunk' && event.content) {
                        onChunk(event.content);
                    } else if (event.type === 'done' && event.message) {
                        onAssistantMessage(event.message);
                    }
                }
            }
        } catch {
            setError('Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    }

    if (isLocked) {
        return (
            <div className="border-t p-4">
                <p className="text-center text-sm text-muted-foreground">
                    This conversation is locked because all attached documents
                    have been deleted.
                </p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="border-t p-4">
            <div className="flex items-end gap-2">
                <textarea
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Ask a question about your documents..."
                    rows={1}
                    disabled={loading}
                    className="flex-1 resize-none rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-50"
                    style={{ maxHeight: '8rem', overflowY: 'auto' }}
                    onInput={(e) => {
                        const target = e.target as HTMLTextAreaElement;
                        target.style.height = 'auto';
                        target.style.height = `${target.scrollHeight}px`;
                    }}
                />
                <Button
                    type="submit"
                    size="icon"
                    disabled={!content.trim() || loading}
                >
                    <SendHorizontal className="h-4 w-4" />
                </Button>
            </div>
            {loading && (
                <p className="mt-2 text-xs text-muted-foreground">
                    Thinking...
                </p>
            )}
            {error && <p className="mt-2 text-xs text-destructive">{error}</p>}
        </form>
    );
}
