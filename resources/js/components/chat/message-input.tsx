import { SendHorizontal } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { apiFetch } from '@/lib/api';
import { store } from '@/routes/messages';
import type { Message } from '@/types/conversations';

interface MessageInputProps {
    conversationId: number;
    onMessages: (userMessage: Message, assistantMessage: Message) => void;
    onTitle?: (title: string) => void;
    isLocked?: boolean;
}

export default function MessageInput({
    conversationId,
    onMessages,
    onTitle,
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
            const response = await apiFetch(store(conversationId).url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content }),
            });

            if (!response.ok) {
                setError('Something went wrong. Please try again.');

                return;
            }

            const data = await response.json();
            onMessages(data.userMessage, data.assistantMessage);
            onTitle?.(data.title);
            setContent('');
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
