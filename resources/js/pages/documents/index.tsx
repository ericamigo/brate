import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DeleteDocumentButton } from '@/components/delete-document-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pages } from '@/components/pagination';
import { date } from '@/lib/date';
import type { Pagination } from '@/components/pagination';

type Document = {
    id: number;
    filename: string;
    created_at: string;
    updated_at: string;
};

export default function Index({
    documents,
}: {
    documents: Pagination & { data: Document[] };
}) {
    const [open, setOpen] = useState(false);
    const form = useForm<{ file: File | null }>({ file: null });

    function changeOpen(value: boolean) {
        if (form.processing) return;
        setOpen(value);
        form.reset();
        form.clearErrors();
    }

    function upload(event: FormEvent) {
        event.preventDefault();
        form.post('/documents', {
            forceFormData: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
                form.clearErrors();
            },
        });
    }

    return (
        <>
            <Head title="Documents" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Documents
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {documents.total} documents
                        </p>
                    </div>
                    <Dialog open={open} onOpenChange={changeOpen}>
                        <DialogTrigger asChild>
                            <Button>Upload document</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Upload document</DialogTitle>
                                <DialogDescription>
                                    Choose a file to upload. Maximum size: 20
                                    MB.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={upload} className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="document-file">File</Label>
                                    <Input
                                        id="document-file"
                                        type="file"
                                        required
                                        disabled={form.processing}
                                        onChange={(event) =>
                                            form.setData(
                                                'file',
                                                event.target.files?.[0] ?? null,
                                            )
                                        }
                                        aria-invalid={!!form.errors.file}
                                        aria-describedby={
                                            form.errors.file
                                                ? 'document-file-error'
                                                : undefined
                                        }
                                    />
                                    {form.errors.file && (
                                        <p
                                            id="document-file-error"
                                            role="alert"
                                            className="text-sm text-destructive"
                                        >
                                            {form.errors.file}
                                        </p>
                                    )}
                                </div>
                                {form.progress && (
                                    <progress
                                        value={form.progress.percentage}
                                        max={100}
                                        aria-label="Upload progress"
                                        className="w-full"
                                    />
                                )}
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={form.processing}
                                        onClick={() => changeOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={
                                            form.processing || !form.data.file
                                        }
                                    >
                                        {form.processing
                                            ? 'Uploading…'
                                            : 'Upload'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-left text-sm">
                        {documents.data.length > 0 && (
                            <thead className="text-xs whitespace-nowrap">
                                <tr className="whitespace-nowrap">
                                    <th className="px-5 py-4">Filename</th>
                                    <th className="px-5 py-4">Uploaded</th>
                                    <th className="px-5 py-4 text-right" />
                                </tr>
                            </thead>
                        )}
                        <tbody>
                            {documents.data.map((document) => (
                                <tr
                                    key={document.id}
                                    className="border-t whitespace-nowrap"
                                >
                                    <td className="px-5 py-4 break-all">
                                        {document.filename}
                                    </td>
                                    <td className="px-5 py-4">
                                        {date(document.created_at)}
                                    </td>
                                    <td className="px-5 py-4 text-right">
                                        <div className="-my-2 flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={`/documents/${document.id}`}
                                                    aria-label={`Download ${document.filename}`}
                                                >
                                                    Download
                                                </a>
                                            </Button>
                                            <DeleteDocumentButton
                                                document={document}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {!documents.data.length && (
                                <tr className="whitespace-nowrap">
                                    <td
                                        colSpan={3}
                                        className="p-12 text-center text-muted-foreground"
                                    >
                                        No documents uploaded yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Pages pagination={documents} />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Documents', href: '/documents' }],
};
