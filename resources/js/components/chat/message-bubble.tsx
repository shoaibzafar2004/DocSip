import Markdown from 'react-markdown';
import type { Message } from '@/types/conversations';

interface MessageBubbleProps {
    message: Message;
}

export default function MessageBubble({ message }: MessageBubbleProps) {
    const isUser = message.role === 'user';

    return (
        <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div
                className={`max-w-[75%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed ${
                    isUser
                        ? 'bg-primary text-primary-foreground rounded-br-sm'
                        : 'bg-muted text-foreground rounded-bl-sm'
                }`}
            >
                {isUser ? (
                    message.content
                ) : (
                    <Markdown
                        components={{
                            p: ({ children }) => <p className="mb-2 last:mb-0">{children}</p>,
                            ul: ({ children }) => <ul className="mb-2 list-disc pl-4 last:mb-0">{children}</ul>,
                            ol: ({ children }) => <ol className="mb-2 list-decimal pl-4 last:mb-0">{children}</ol>,
                            li: ({ children }) => <li className="mb-0.5">{children}</li>,
                            strong: ({ children }) => <strong className="font-semibold">{children}</strong>,
                            h1: ({ children }) => <h1 className="mb-1 text-base font-bold">{children}</h1>,
                            h2: ({ children }) => <h2 className="mb-1 font-semibold">{children}</h2>,
                            h3: ({ children }) => <h3 className="mb-1 font-semibold">{children}</h3>,
                            code: ({ children }) => <code className="rounded bg-black/10 px-1 py-0.5 font-mono text-xs">{children}</code>,
                        }}
                    >
                        {message.content}
                    </Markdown>
                )}
            </div>
        </div>
    );
}
