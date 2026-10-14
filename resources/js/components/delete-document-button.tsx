import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

type Props = { document: { id: number; filename: string } };

export function DeleteDocumentButton({ document }: Props) {
    const [processing, setProcessing] = useState(false);
    function remove() {
        if (
            !window.confirm(
                `Delete ${document.filename}? The file will be permanently deleted.`,
            )
        )
            return;
        router.delete(`/documents/${document.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    }
    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="text-destructive"
            disabled={processing}
            onClick={remove}
            aria-label={`Delete ${document.filename}`}
        >
            {processing ? 'Deleting…' : 'Delete'}
        </Button>
    );
}
