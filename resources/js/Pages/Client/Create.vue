<template>
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Project Registration</h1>

    <div class="mb-4 flex gap-2 text-sm">
      <span :class="stepClass(1)">1. Client</span>
      <span :class="stepClass(2)">2. Project</span>
      <span :class="stepClass(3)">3. Attachments</span>
    </div>

    <div v-if="flash && flash.success" class="p-3 bg-green-100 text-green-700 rounded mb-4">{{ flash.success }}</div>

    <!-- Step 1: Client Info -->
    <form v-if="step === 1" @submit.prevent="nextStep" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Client Name</label>
        <input v-model="formData.name" class="input" type="text" required />
        <p v-if="errors.name" class="error">{{ errors.name }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input v-model="formData.email" class="input" type="email" required />
        <p v-if="errors.email" class="error">{{ errors.email }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium">Phone</label>
        <input v-model="formData.phone" class="input" type="tel" required />
        <p v-if="errors.phone" class="error">{{ errors.phone }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium">Industry</label>
        <input v-model="formData.industry" class="input" type="text" />
        <p v-if="errors.industry" class="error">{{ errors.industry }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium">Project Type</label>
        <select v-model="formData.project_type" class="input" required>
          <option value="">Select a project type</option>
          <option value="web_app">Web Application</option>
          <option value="mobile_app">Mobile Application</option>
          <option value="erp">ERP System</option>
          <option value="e_commerce">E-commerce</option>
        </select>
        <p v-if="errors.project_type" class="error">{{ errors.project_type }}</p>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="btn">Next</button>
      </div>
    </form>

    <!-- Step 2: Project Info -->
    <form v-else-if="step === 2" @submit.prevent="nextStep" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Project Name</label>
        <input v-model="formData.project_name" class="input" type="text" required />
        <p v-if="errors.project_name" class="error">{{ errors.project_name }}</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Start Date</label>
          <input v-model="formData.start_date" class="input" type="date" />
          <p v-if="errors.start_date" class="error">{{ errors.start_date }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium">End Date</label>
          <input v-model="formData.end_date" class="input" type="date" :min="formData.start_date" />
          <p v-if="errors.end_date" class="error">{{ errors.end_date }}</p>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium">Estimated Budget</label>
        <input v-model="formData.estimated_budget" class="input" type="number" min="0" step="0.01" />
        <p v-if="errors.estimated_budget" class="error">{{ errors.estimated_budget }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium">Project Description</label>
        <textarea v-model="formData.description" class="input" rows="4"></textarea>
        <p v-if="errors.description" class="error">{{ errors.description }}</p>
      </div>
      <div class="flex justify-between">
        <button type="button" @click="prevStep" class="btn-secondary">Back</button>
        <button type="submit" class="btn">Next</button>
      </div>
    </form>

    <!-- Step 3: Attachments -->
    <form v-else @submit.prevent="submitForm" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Attachments</label>
        <input class="input" type="file" multiple @change="handleFiles" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" />
        <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
          <div v-for="(preview, index) in previews" :key="index" class="relative group">
            <div v-if="preview.isImage" class="aspect-square overflow-hidden rounded border">
              <img :src="preview.url" :alt="preview.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="aspect-square border rounded flex items-center justify-center bg-gray-50">
              <span class="text-xs text-gray-500">{{ preview.name }}</span>
            </div>
            <button 
              type="button" 
              @click="removeFile(index)" 
              class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
            >
              ×
            </button>
          </div>
        </div>
        <p v-if="errors.attachments" class="error">{{ errors.attachments }}</p>
        <p v-if="errors['attachments.*']" class="error">{{ errors['attachments.*'] }}</p>
      </div>
      <div class="flex gap-2">
        <button type="button" @click="prevStep" class="btn-secondary">Back</button>
        <button type="submit" class="btn" :disabled="isSubmitting">
          <span v-if="isSubmitting">Submitting...</span>
          <span v-else>Submit</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { ref, reactive, computed } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash || {})
const errors = reactive({})
const isSubmitting = ref(false)

// Form data
const formData = reactive({
  // Client data
  name: '',
  email: '',
  phone: '',
  industry: '',
  project_type: '',
  
  // Project data
  project_name: '',
  start_date: '',
  end_date: '',
  estimated_budget: '',
  description: ''
})

// Files handling
const files = ref([])
const previews = ref([])

// Step handling
const step = ref(1)

function stepClass(n) {
  return n === step.value ? 'px-2 py-1 rounded bg-blue-600 text-white' : 'px-2 py-1 rounded bg-gray-100'
}

function nextStep() {
  step.value++
}

function prevStep() {
  step.value--
}

function handleFiles(ev) {
  const newFiles = Array.from(ev.target.files)
  files.value = [...files.value, ...newFiles]
  
  const newPreviews = newFiles.map(f => ({
    name: f.name,
    isImage: f.type.startsWith('image/'),
    url: f.type.startsWith('image/') ? URL.createObjectURL(f) : ''
  }))
  
  previews.value = [...previews.value, ...newPreviews]
  ev.target.value = null // Reset file input to allow selecting the same file again
}

function removeFile(index) {
  files.value.splice(index, 1)
  previews.value.splice(index, 1)
}

function submitForm() {
  isSubmitting.value = true
  const formData = new FormData()
  
  // Add all form data
  Object.entries(formData).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      formData.append(key, value)
    }
  })
  
  // Add files
  files.value.forEach((file, index) => {
    formData.append(`attachments[${index}]`, file)
  })
  
  // Send all data in a single request
  router.post('/projects/register/finalize', formData, {
    forceFormData: true,
    preserveScroll: true,
    onError: (e) => {
      isSubmitting.value = false
      Object.assign(errors, e)
      // If there are validation errors, go to the first step with errors
      const errorFields = Object.keys(e)
      if (errorFields.some(field => ['name', 'email', 'phone', 'industry', 'project_type'].includes(field))) {
        step.value = 1
      } else if (errorFields.some(field => ['project_name', 'start_date', 'end_date', 'estimated_budget', 'description'].includes(field))) {
        step.value = 2
      }
    },
    onSuccess: () => {
      // Reset form
      Object.keys(formData).forEach(key => {
        if (Array.isArray(formData[key])) {
          formData[key] = []
        } else if (typeof formData[key] === 'object') {
          formData[key] = {}
        } else {
          formData[key] = ''
        }
      })
      files.value = []
      previews.value = []
      step.value = 1
      isSubmitting.value = false
    }
  })
}
</script>

<style scoped>
@reference '../../../css/app.css';
.input { @apply w-full border rounded px-3 py-2 text-sm; }
.btn { @apply bg-blue-600 text-white px-4 py-2 rounded; }
.btn-secondary { @apply bg-gray-200 text-gray-700 px-4 py-2 rounded; }
.error { @apply text-red-600 text-xs mt-1; }
</style>
