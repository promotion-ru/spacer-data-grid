<template>
  <div class="card flex flex-wrap gap-4">
    <div class="flex-auto">
      <label for="buttondisplay" class="font-bold block mb-2"> Button </label>
      <DatePicker v-model="buttondisplay" showIcon fluid :showOnFocus="false" inputId="buttondisplay" />
    </div>
    <div class="flex-auto">
      <label for="icondisplay" class="font-bold block mb-2"> Default Icon </label>
      <DatePicker v-model="icondisplay" showIcon fluid iconDisplay="input" inputId="icondisplay" />
    </div>
    <div class="flex-auto">
      <label for="templatedisplay" class="font-bold block mb-2"> Custom Icon </label>
      <DatePicker v-model="templatedisplay" showIcon fluid iconDisplay="input" timeOnly inputId="templatedisplay">
        <template #inputicon="slotProps">
          <i class="pi pi-clock" @click="slotProps.clickCallback" />
        </template>
      </DatePicker>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const buttondisplay = ref();
const icondisplay = ref();
const templatedisplay = ref();
</script>