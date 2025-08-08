<template>
    <div class="flex flex-col">
        <div class="relative">
            <!-- Left Icon -->
            <div v-if="leftIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <component :is="leftIcon" :class="iconClasses" />
            </div>

            <input
                :type="type"
                :value="modelValue"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :class="inputClasses"
                :style="widthStyle"
                v-bind="$attrs"
            />

            <!-- Right Icon -->
            <div v-if="rightIcon" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <component :is="rightIcon" :class="iconClasses" />
            </div>

            <!-- Clear Button -->
            <button
                v-if="clearable && modelValue && !disabled"
                @click="clearInput"
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center hover:text-gray-700"
            >
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Error Message -->
        <span v-if="error && errorMessage" class="mt-1 text-xs text-red-500">
    {{ errorMessage }}
    </span>

        <!-- Helper Text -->
        <span v-if="helperText && !error" class="mt-1 text-xs text-gray-500">
      {{ helperText }}
    </span>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text',
        validator: (value) => ['text', 'email', 'password', 'number', 'tel', 'url', 'search'].includes(value)
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'outline', 'minimal', 'filled'].includes(value)
    },
    width: {
        type: String,
        default: 'auto'
    },
    error: {
        type: Boolean,
        default: false
    },
    errorMessage: {
        type: String,
        default: ''
    },
    helperText: {
        type: String,
        default: ''
    },
    leftIcon: {
        type: [String, Object],
        default: null
    },
    rightIcon: {
        type: [String, Object],
        default: null
    },
    clearable: {
        type: Boolean,
        default: false
    },
    focusRing: {
        type: String,
        default: 'blue',
        validator: (value) => ['blue', 'green', 'red', 'purple', 'yellow'].includes(value)
    }
});

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'clear']);

const isFocused = ref(false);

const inputClasses = computed(() => {
    const baseClasses = 'border rounded-lg focus:outline-none transition-colors';

    // Size classes
    const sizeClasses = {
        xs: 'px-2 py-1 text-xs',
        sm: 'px-3 py-1 text-sm',
        md: 'px-3 py-1.5 text-sm',
        lg: 'px-4 py-2 text-base'
    };

    // Variant classes
    const variantClasses = {
        default: 'border-gray-300 bg-white',
        outline: 'border-gray-400 bg-transparent',
        minimal: 'border-gray-200 bg-gray-50',
        filled: 'border-transparent bg-gray-100'
    };

    // Focus ring classes
    const focusRingClasses = {
        blue: 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
        green: 'focus:ring-2 focus:ring-green-500 focus:border-green-500',
        red: 'focus:ring-2 focus:ring-red-500 focus:border-red-500',
        purple: 'focus:ring-2 focus:ring-purple-500 focus:border-purple-500',
        yellow: 'focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500'
    };

    // Error classes
    const errorClasses = props.error
        ? 'border-red-500 focus:ring-2 focus:ring-red-500 focus:border-red-500'
        : focusRingClasses[props.focusRing];

    // Disabled classes
    const disabledClasses = props.disabled
        ? 'opacity-50 cursor-not-allowed bg-gray-100'
        : '';

    // Icon padding classes
    const leftPadding = props.leftIcon ? 'pl-10' : '';
    const rightPadding = (props.rightIcon || props.clearable) ? 'pr-10' : '';

    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        errorClasses,
        disabledClasses,
        leftPadding,
        rightPadding
    ].filter(Boolean).join(' ');
});

// Icon classes
const iconClasses = computed(() => {
    const sizeClasses = {
        xs: 'w-3 h-3',
        sm: 'w-4 h-4',
        md: 'w-4 h-4',
        lg: 'w-5 h-5'
    };

    return `${sizeClasses[props.size]} text-gray-400`;
});

// Width style
const widthStyle = computed(() => {
    if (props.width === 'auto') return {};
    return { width: props.width };
});

const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
};

const handleFocus = (event) => {
    isFocused.value = true;
    emit('focus', event);
};

const handleBlur = (event) => {
    isFocused.value = false;
    emit('blur', event);
};

const clearInput = () => {
    emit('update:modelValue', '');
    emit('clear');
};
</script>
