<template>
  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Project Registration</h1>
        <p class="mt-2 text-sm text-gray-600">Complete the following steps to register your project</p>
        
        <!-- Progress Steps -->
        <div class="mt-6">
          <nav class="flex items-center justify-center">
            <ol role="list" class="flex items-center space-x-5">
              <li v-for="(step, index) in steps" :key="step.id">
                <div class="relative flex items-center">
                  <span v-if="step.status === 'complete'" class="flex items-center justify-center w-10 h-10 bg-indigo-600 rounded-full">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  <span v-else-if="step.status === 'current'" class="flex items-center justify-center w-10 h-10 bg-white border-2 border-indigo-600 rounded-full">
                    <span class="text-indigo-600">{{ step.id }}</span>
                  </span>
                  <span v-else class="flex items-center justify-center w-10 h-10 bg-white border-2 border-gray-300 rounded-full">
                    <span class="text-gray-500">{{ step.id }}</span>
                  </span>
                  
                  <span v-if="index !== steps.length - 1" class="absolute top-5 -right-14 w-14 h-0.5 bg-gray-300"></span>
                </div>
                <span class="mt-2 block text-sm font-medium text-gray-700">{{ step.name }}</span>
              </li>
            </ol>
          </nav>
        </div>
      </div>
      
      <!-- Form Content -->
      <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
        <slot></slot>
      </div>
      
      <!-- Navigation Buttons -->
      <div class="mt-8 flex justify-between">
        <button 
          v-if="currentStep > 1"
          @click="goToStep(currentStep - 1)"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Previous
        </button>
        <div v-else></div>
        
        <button 
          v-if="currentStep < totalSteps"
          @click="goToStep(currentStep + 1)"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Next
        </button>
        <button 
          v-else
          type="submit"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          Submit Project
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  currentStep: {
    type: Number,
    required: true,
    default: 1
  },
  totalSteps: {
    type: Number,
    required: true,
    default: 3
  }
});

const steps = computed(() => [
  { id: 1, name: 'Project Info', status: props.currentStep > 1 ? 'complete' : props.currentStep === 1 ? 'current' : 'upcoming' },
  { id: 2, name: 'Client Details', status: props.currentStep > 2 ? 'complete' : props.currentStep === 2 ? 'current' : 'upcoming' },
  { id: 3, name: 'Review', status: props.currentStep === 3 ? 'current' : 'upcoming' },
]);

const emit = defineEmits(['step-change']);

const goToStep = (step) => {
  emit('step-change', step);
};
</script>
