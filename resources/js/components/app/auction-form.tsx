import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AuctionFormValues, AuctionType } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type AuctionFormData = {
    title: string;
    type: string;
    status: string;
    start_date: string;
    start_time: string;
    end_date: string;
    end_time: string;
};

type AuctionFormProps = {
    action: RouteFormDefinition<'post'> | RouteFormDefinition<'put'>;
    auction?: AuctionFormValues;
    types: string[];
    statuses: string[];
    submitLabel: string;
};

const labelize = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');

export const AuctionForm = ({
    action,
    auction,
    types,
    statuses,
    submitLabel,
}: AuctionFormProps) => {
    const [type, setType] = useState<AuctionType>(auction?.type ?? 'ongoing');

    return (
        <Form<AuctionFormData> {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>

                        <Input
                            id="title"
                            name="title"
                            defaultValue={auction?.title}
                            required
                            autoFocus
                            placeholder="November Watch Sale"
                        />

                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="type">Type</Label>

                            <Select
                                name="type"
                                value={type}
                                onValueChange={(value) =>
                                    setType(value as AuctionType)
                                }
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {types.map((option) => (
                                        <SelectItem key={option} value={option}>
                                            {labelize(option)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={errors.type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>

                            <Select
                                name="status"
                                defaultValue={auction?.status ?? 'draft'}
                            >
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem key={option} value={option}>
                                            {labelize(option)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={errors.status} />
                        </div>
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="start_date">Start date</Label>

                            <Input
                                id="start_date"
                                name="start_date"
                                type="date"
                                defaultValue={auction?.start_date}
                                required
                            />

                            <InputError message={errors.start_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="start_time">Start time</Label>

                            <Input
                                id="start_time"
                                name="start_time"
                                type="time"
                                defaultValue={auction?.start_time}
                                required
                            />

                            <InputError message={errors.start_time} />
                        </div>
                    </div>

                    {type === 'ongoing' && (
                        <div className="grid gap-6 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End date</Label>

                                <Input
                                    id="end_date"
                                    name="end_date"
                                    type="date"
                                    defaultValue={auction?.end_date ?? ''}
                                />

                                <InputError message={errors.end_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="end_time">End time</Label>

                                <Input
                                    id="end_time"
                                    name="end_time"
                                    type="time"
                                    defaultValue={auction?.end_time ?? ''}
                                />

                                <InputError message={errors.end_time} />
                            </div>
                        </div>
                    )}

                    <Button disabled={processing}>{submitLabel}</Button>
                </>
            )}
        </Form>
    );
};
