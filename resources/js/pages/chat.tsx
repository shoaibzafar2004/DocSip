import { Head } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import { useState } from 'react';
import DocumentPicker from '@/components/chat/document-picker';
import { conversations } from '@/routes';

export default function Chat() {
    const [pickerOpen, setPickerOpen] = useState(true);

    return (
        <>
            <Head title="Chat" />

            <div className="flex h-full flex-col items-center justify-center gap-3">
                <MessageSquare className="text-muted-foreground/40 h-12 w-12" />
                <p className="text-muted-foreground text-sm">
                    Select documents to start a new conversation.
                </p>
            </div>

            <DocumentPicker open={pickerOpen} onOpenChange={setPickerOpen} />
        </>
    );
}

Chat.layout = {
    breadcrumbs: [
        {
            title: 'Chat',
            href: conversations(),
        },
    ],
};
