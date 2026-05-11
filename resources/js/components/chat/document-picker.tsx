import { router, usePage } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { store } from '@/routes/conversations';

interface DocumentPickerProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function DocumentPicker({ open, onOpenChange }: DocumentPickerProps) {
    const { readyDocuments } = usePage<{
        readyDocuments?: { id: number; name: string }[];
    }>().props;

    const documents = readyDocuments ?? [];
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    function toggle(id: number) {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    }

    function handleSubmit() {
        router.post(store().url, { document_ids: selectedIds }, {
            onSuccess: () => {
                setSelectedIds([]);
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Start a new chat</DialogTitle>
                    <DialogDescription>
                        Select one or more documents to chat with.
                    </DialogDescription>
                </DialogHeader>

                {documents.length === 0 ? (
                    <p className="text-muted-foreground py-4 text-center text-sm">
                        No ready documents. Upload and process a document first.
                    </p>
                ) : (
                    <ul className="flex max-h-72 flex-col gap-2 overflow-y-auto">
                        {documents.map((doc) => (
                            <li key={doc.id}>
                                <label className="hover:bg-muted flex cursor-pointer items-center gap-3 rounded-md p-2">
                                    <Checkbox
                                        checked={selectedIds.includes(doc.id)}
                                        onCheckedChange={() => toggle(doc.id)}
                                    />
                                    <FileText className="text-muted-foreground h-4 w-4 shrink-0" />
                                    <span className="truncate text-sm">{doc.name}</span>
                                </label>
                            </li>
                        ))}
                    </ul>
                )}

                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        disabled={selectedIds.length === 0}
                        onClick={handleSubmit}
                    >
                        Start Chat
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
