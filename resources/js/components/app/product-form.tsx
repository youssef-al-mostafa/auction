import { Form } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { ProductFormValues } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type ProductFormData = {
    name: string;
    description: string;
    images: File[];
};

type ProductFormProps = {
    action: RouteFormDefinition<'post'> | RouteFormDefinition<'put'>;
    product?: ProductFormValues;
    submitLabel: string;
};

export const ProductForm = ({
    action,
    product,
    submitLabel,
}: ProductFormProps) => {
    const [removed, setRemoved] = useState<number[]>([]);

    const existingImages = (product?.images ?? []).filter(
        (image) => !removed.includes(image.id),
    );

    return (
        <Form<ProductFormData> {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>

                        <Input
                            id="name"
                            name="name"
                            defaultValue={product?.name}
                            required
                            autoFocus
                            placeholder="Vintage Omega Seamaster"
                        />

                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>

                        <Textarea
                            id="description"
                            name="description"
                            defaultValue={product?.description ?? ''}
                            rows={6}
                            placeholder="Condition, provenance, anything a bidder should know."
                        />

                        <InputError message={errors.description} />
                    </div>

                    {existingImages.length > 0 && (
                        <div className="grid gap-2">
                            <Label>Current images</Label>

                            <div className="flex flex-wrap gap-3">
                                {existingImages.map((image) => (
                                    <div
                                        key={image.id}
                                        className="relative size-24 overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                                    >
                                        <img
                                            src={image.url}
                                            alt={image.name}
                                            className="size-full object-cover"
                                        />

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="icon-xs"
                                            className="absolute top-1 right-1"
                                            onClick={() =>
                                                setRemoved([
                                                    ...removed,
                                                    image.id,
                                                ])
                                            }
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {removed.map((id) => (
                        <input
                            key={id}
                            type="hidden"
                            name="removed_media[]"
                            value={id}
                        />
                    ))}

                    <div className="grid gap-2">
                        <Label htmlFor="images">Add images</Label>

                        <Input
                            id="images"
                            name="images[]"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp,image/avif"
                        />

                        <p className="text-sm text-muted-foreground">
                            Up to 8 images, 5 MB each.
                        </p>

                        <InputError message={errors.images} />
                    </div>

                    <Button disabled={processing}>{submitLabel}</Button>
                </>
            )}
        </Form>
    );
};
