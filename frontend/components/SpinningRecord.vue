<template>
  <span class="spinning-record motion-safe:animate-[spin_1.1s_linear_infinite]" aria-hidden="true">
    <span class="spinning-record__label" />
  </span>
</template>

<style scoped>
/* A tiny LP: pressed grooves, a light sheen so the rotation reads, and a plain label. */
.spinning-record {
  --sheen: color-mix(in srgb, var(--color-pressing) 18%, transparent);
  --sheen-soft: color-mix(in srgb, var(--color-pressing) 10%, transparent);

  position: relative;
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 9999px;
  background:
    conic-gradient(from 20deg, transparent 0 12%, var(--sheen) 16%, transparent 22% 62%, var(--sheen-soft) 66%, transparent 72%),
    repeating-radial-gradient(circle, var(--color-void) 0 1.5px, var(--color-cabinet) 1.5px 3px);
  outline: 1px solid var(--color-groove);
}

.spinning-record__label {
  width: 36%;
  height: 36%;
  border-radius: 9999px;
  background: radial-gradient(circle, var(--color-void) 0 14%, var(--color-label) 16%);
}
</style>
