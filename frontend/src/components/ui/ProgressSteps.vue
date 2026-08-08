<script setup lang="ts">
/**
 * ProgressSteps
 * ---------------------------------------------------------------------------
 * Built for checkout, which was previously ONE ~2,000px-tall scroll containing
 * eight unlabelled cards. Shoppers had no idea how much was left, which is one
 * of the best-documented causes of checkout abandonment. A stepper answers
 * "where am I / how many left / can I go back" at a glance.
 *
 * Accessibility:
 *  - an ordered list, so AT announces "step 2 of 4";
 *  - aria-current="step" marks the active item (WCAG 2.4.8 Location);
 *  - completed steps are real buttons, so keyboard users can jump back;
 *  - state is never colour-only - completed shows a tick, current shows a ring
 *    and bold text (WCAG 1.4.1).
 */
const props = defineProps<{
  steps: { key: string; label: string }[]
  /** 0-based index of the active step. */
  current: number
  /** Highest step the user has already completed, for back-navigation. */
  furthest?: number
}>()

const emit = defineEmits<{ (e: 'go', index: number): void }>()

const canVisit = (index: number) => index <= (props.furthest ?? props.current)
</script>

<template>
  <nav :aria-label="`Checkout progress, step ${current + 1} of ${steps.length}`">
    <ol class="flex items-start">
      <li
        v-for="(step, index) in steps"
        :key="step.key"
        class="flex flex-1 flex-col items-center"
        :aria-current="index === current ? 'step' : undefined"
      >
        <div class="flex w-full items-center">
          <!-- Left connector, hidden on the first item -->
          <span
            class="h-0.5 flex-1 transition-colors"
            :class="[index === 0 ? 'invisible' : '', index <= current ? 'bg-brand-600' : 'bg-ink-200']"
            aria-hidden="true"
          />

          <component
            :is="canVisit(index) && index !== current ? 'button' : 'div'"
            :type="canVisit(index) && index !== current ? 'button' : undefined"
            class="grid size-9 shrink-0 place-items-center rounded-full border-2 text-xs font-bold transition"
            :class="[
              index < current
                ? 'border-brand-600 bg-brand-600 text-white'
                : index === current
                  ? 'border-brand-600 bg-white text-brand-700 ring-4 ring-brand-100'
                  : 'border-ink-200 bg-white text-ink-400',
              canVisit(index) && index !== current ? 'cursor-pointer hover:border-brand-700' : '',
            ]"
            @click="canVisit(index) && index !== current ? emit('go', index) : undefined"
          >
            <!-- Tick, not just a colour change, marks completion -->
            <i v-if="index < current" class="pi pi-check text-xs" aria-hidden="true" />
            <span v-else aria-hidden="true">{{ index + 1 }}</span>
            <span class="sr-only">
              Step {{ index + 1 }}: {{ step.label }}{{ index < current ? ' (completed)' : '' }}
            </span>
          </component>

          <span
            class="h-0.5 flex-1 transition-colors"
            :class="[
              index === steps.length - 1 ? 'invisible' : '',
              index < current ? 'bg-brand-600' : 'bg-ink-200',
            ]"
            aria-hidden="true"
          />
        </div>

        <span
          class="mt-2 px-1 text-center text-[0.6875rem] leading-tight sm:text-xs"
          :class="index === current ? 'font-semibold text-brand-700' : 'text-ink-500'"
          aria-hidden="true"
        >{{ step.label }}</span>
      </li>
    </ol>
  </nav>
</template>
