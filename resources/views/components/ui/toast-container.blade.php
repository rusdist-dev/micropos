{{-- Container toast global. Sumber data: Alpine.store('toasts'). --}}
<div class="pointer-events-none fixed bottom-6 left-1/2 z-[70] flex w-full max-w-sm -translate-x-1/2 flex-col items-center gap-2 px-4">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-lg"
            :class="{
                'bg-danger-600': t.type === 'danger',
                'bg-primary-600': t.type === 'success',
                'bg-gray-800': t.type === 'info',
            }"
            @click="$store.toasts.remove(t.id)"
        >
            <template x-if="t.type === 'danger'"><x-heroicon-o-exclamation-triangle class="h-5 w-5 flex-shrink-0" /></template>
            <template x-if="t.type === 'success'"><x-heroicon-o-check-circle class="h-5 w-5 flex-shrink-0" /></template>
            <template x-if="t.type === 'info'"><x-heroicon-o-information-circle class="h-5 w-5 flex-shrink-0" /></template>
            <span class="flex-1" x-text="t.message"></span>
        </div>
    </template>
</div>
