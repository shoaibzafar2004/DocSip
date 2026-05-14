import { Head, router } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import MessageBubble from '@/components/chat/message-bubble';
import MessageInput from '@/components/chat/message-input';
import { Badge } from '@/components/ui/badge';
import { conversations } from '@/routes';
import type { Conversation, Message } from '@/types/conversations';

interface AttachedDocument {
    id: number;
    name: string;
}

interface ChatShowProps {
    conversation: Conversation;
    messages: Message[];
    attachedDocuments: AttachedDocument[];
    isLocked: boolean;
}

export default function ChatShow({
    conversation,
    messages: initialMessages,
    attachedDocuments,
    isLocked,
}: ChatShowProps) {
    const [messages, setMessages] = useState<Message[]>(initialMessages);
    const [title, setTitle] = useState(conversation.title);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    function handleMessages(userMessage: Message, assistantMessage: Message) {
        setMessages((prev) => [...prev, userMessage, assistantMessage]);
    }

    function handleTitle(newTitle: string) {
        setTitle(newTitle);
        router.reload({ only: ['conversations'] });
    }

    return (
        <>
            <Head title={title ?? 'Conversation'} />

            <div className="flex h-full flex-col">
                <div className="border-b px-4 py-3">
                    <h2 className="text-sm font-semibold">
                        {title ?? 'Untitled conversation'}
                    </h2>
                    {attachedDocuments.length > 0 && (
                        <div className="mt-1.5 flex flex-wrap gap-1.5">
                            {attachedDocuments.map((doc) => (
                                <Badge
                                    key={doc.id}
                                    variant="secondary"
                                    className="gap-1 text-xs font-normal"
                                >
                                    <FileText className="h-3 w-3" />
                                    {doc.name}
                                </Badge>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex-1 overflow-y-auto px-4 py-4">
                    {messages.length === 0 ? (
                        <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
                            Ask a question about your documents.
                        </div>
                    ) : (
                        <div className="flex flex-col gap-4">
                            {messages.map((message) => (
                                <MessageBubble
                                    key={message.id}
                                    message={message}
                                />
                            ))}
                            <div ref={bottomRef} />
                        </div>
                    )}
                </div>

                <MessageInput
                    conversationId={conversation.id}
                    onMessages={handleMessages}
                    onTitle={handleTitle}
                    isLocked={isLocked}
                />
            </div>
        </>
    );
}

ChatShow.layout = {
    breadcrumbs: [
        {
            title: 'Chat',
            href: conversations(),
        },
    ],
};
