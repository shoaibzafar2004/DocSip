import { router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { apiFetch } from '@/lib/api';
import { destroy, show } from '@/routes/conversations';
import type { Conversation } from '@/types/conversations';
import TextLink from '../text-link';

export default function ConversationList() {
    const { conversations } = usePage<{ conversations?: Conversation[] }>().props;
    const list = conversations ?? [];
    const [pendingDelete, setPendingDelete] = useState<Conversation | null>(null);

    async function handleConfirmDelete() {
        if (!pendingDelete) {
            return;
        }

        await apiFetch(destroy.url(pendingDelete), { method: 'DELETE' });
        setPendingDelete(null);
        router.reload({ only: ['conversations'] });
    }

    return (
        <>
            <div className="flex flex-1 flex-col gap-2 overflow-x-auto p-2">
                <p className="px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Conversations
                </p>

                {list.length === 0 && (
                    <p className="px-2 text-xs text-muted-foreground">No conversations yet.</p>
                )}

                {list.length > 0 && (
                    <ul className="flex flex-col gap-0.5">
                        {list.map((conversation) => (
                            <li
                                key={conversation.id}
                                className="group flex items-center gap-1 rounded-md px-2 py-1.5 hover:bg-sidebar-accent"
                            >
                                <TextLink
                                    href={show.url(conversation)}
                                    className="flex-1 truncate text-sm text-sidebar-foreground no-underline hover:no-underline"
                                >
                                    {conversation.title ?? 'Untitled conversation'}
                                </TextLink>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-6 w-6 shrink-0 opacity-0 group-hover:opacity-100 focus:opacity-100"
                                        >
                                            <MoreHorizontal className="h-3.5 w-3.5" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" side="right">
                                        <DropdownMenuItem
                                            className="text-destructive focus:text-destructive"
                                            onSelect={() => setPendingDelete(conversation)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <AlertDialog open={pendingDelete !== null} onOpenChange={(open) => !open && setPendingDelete(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete conversation</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium text-foreground">
                                {pendingDelete?.title ?? 'this conversation'}
                            </span>
                            ? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleConfirmDelete}>
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
