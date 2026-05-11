import { usePage } from '@inertiajs/react';
import { show } from '@/routes/conversations';
import type { Conversation } from '@/types/conversations';
import TextLink from '../text-link';

export default function ConversationList() {
    const { conversations } = usePage<{ conversations?: Conversation[] }>()
        .props;
    const list = conversations ?? [];

    return (
        <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h5 className="text-sm font-bold">Conversations</h5>
            {list.length === 0 && (
                <p className="text-xs text-muted-foreground">
                    No conversations yet.
                </p>
            )}

            {list.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {list.map((conversation) => (
                        <li
                            key={conversation.id}
                            className="rounded-md p-2 hover:bg-muted"
                        >
                            <TextLink href={show.url(conversation)}>
                                {conversation.title || `Untitled conversation`}
                            </TextLink>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
