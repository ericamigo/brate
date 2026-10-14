import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
import { Pages, Shell, date } from '../business-rates/shared';
import type { Pagination } from '../business-rates/shared';

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
        <Shell title="Documents">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold">Documents</h1>
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
                                Choose a file to upload. Maximum size: 20 MB.
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
                                    {form.processing ? 'Uploading…' : 'Upload'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted">
                        <tr>
                            <th className="px-5 py-4">Filename</th>
                            <th className="px-5 py-4">Uploaded</th>
                            <th className="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {documents.data.map((document) => (
                            <tr key={document.id} className="border-t">
                                <td className="px-5 py-4 break-all">
                                    {document.filename}
                                </td>
                                <td className="px-5 py-4">
                                    {date(document.created_at)}
                                </td>
                                <td className="px-5 py-4 text-right">
                                    <Button variant="outline" size="sm" asChild>
                                        <a
                                            href={`/documents/${document.id}`}
                                            aria-label={`Download ${document.filename}`}
                                        >
                                            Download
                                        </a>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                        {!documents.data.length && (
                            <tr>
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
        </Shell>
    );
}
