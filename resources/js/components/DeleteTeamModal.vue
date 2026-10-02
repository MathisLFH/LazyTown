<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy as destroyAdminTeam } from '@/routes/admin/teams';
import { destroy } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team;
    open: boolean;
    administration?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    administration: false,
});
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const confirmationName = ref('');
const formKey = ref(0);

const canDeleteTeam = computed(() => {
    return confirmationName.value === props.team.name;
});
const deleteForm = computed(() =>
    props.administration
        ? destroyAdminTeam.form(props.team.slug)
        : destroy.form(props.team.slug),
);

const handleOpenChange = (nextOpen: boolean) => {
    emit('update:open', nextOpen);

    if (!nextOpen) {
        confirmationName.value = '';
        formKey.value++;
    }
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent>
            <Form
                :key="formKey"
                v-bind="deleteForm"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="handleOpenChange(false)"
            >
                <DialogHeader>
                    <DialogTitle>Bist du sicher?</DialogTitle>
                    <DialogDescription>
                        Diese Aktion kann nicht rückgängig gemacht werden. Dies
                        wird das Team dauerhaft löschen
                        <strong>"{{ props.team.name }}"</strong>.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="confirmation-name">
                            Gib
                            <strong>"{{ props.team.name }}"</strong> ein, um zu
                            bestätigen
                        </Label>
                        <Input
                            id="confirmation-name"
                            name="name"
                            data-test="delete-team-name"
                            v-model="confirmationName"
                            placeholder="Team Name eingeben"
                            autocomplete="off"
                        />
                        <InputError :message="errors.name" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary"> Abbrechen </Button>
                    </DialogClose>

                    <Button
                        data-test="delete-team-confirm"
                        variant="destructive"
                        type="submit"
                        :disabled="!canDeleteTeam || processing"
                    >
                        Team löschen
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
