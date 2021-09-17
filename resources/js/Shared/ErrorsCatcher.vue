<template>
  <div class="v-text-field__details mt-3">
    <div class="v-messages theme--light error--text" role="alert">
      <div class="v-messages__message" v-for="error in getValidationErrors(bag, actualWildcards, actualExcludeMode)">
        <span class="red--text">- {{ error }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    bag: Object|Array,
    wildcards: Array|String,
    excludeMode: Boolean
  },

  data() {
    return {
      actualWildcards: this.wildcards ?? [],
      actualExcludeMode: this.excludeMode ?? false
    }
  }
}
</script>
