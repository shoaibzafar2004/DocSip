import { SendHorizontal } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/messages';
import type { Message } from '@/types/conversations';

interface MessageInputProps {
    conversationId: number;
    onMessages: (userMessage: Message, assistantMessage: Message) => void;
}

export default function MessageInput({ conversationId, onMessages }: MessageInputProps) {
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
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie
                            .split('; ')
                            .find((c) => c.startsWith('XSRF-TOKEN='))
                            ?.split('=')[1] ?? '',
                    ),
                },
                body: JSON.stringify({ content }),
            });

            if (!response.ok) {
                setError('Something went wrong. Please try again.');
                return;
            }

            const data = await response.json();
            onMessages(data.userMessage, data.assistantMessage);
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
                    className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex-1 resize-none rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none disabled:opacity-50"
                    style={{ maxHeight: '8rem', overflowY: 'auto' }}
                    onInput={(e) => {
                        const target = e.target as HTMLTextAreaElement;
                        target.style.height = 'auto';
                        target.style.height = `${target.scrollHeight}px`;
                    }}
                />
                <Button type="submit" size="icon" disabled={!content.trim() || loading}>
                    <SendHorizontal className="h-4 w-4" />
                </Button>
            </div>
            {loading && (
                <p className="text-muted-foreground mt-2 text-xs">Thinking...</p>
            )}
            {error && (
                <p className="text-destructive mt-2 text-xs">{error}</p>
            )}
        </form>
    );
}
