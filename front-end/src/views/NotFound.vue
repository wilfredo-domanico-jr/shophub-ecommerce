<template>
  <div class="not-found-page">
    <h1>404</h1>
    <p>Oops! The page you are looking for does not exist.</p>
    <router-link to="/" class="back-home">Go Back Home</router-link>
  </div>
</template>

<style scoped>
.not-found-page {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 80vh;
  text-align: center;
  padding: 2rem;
}

.not-found-page h1 {
  font-size: 6rem;
  margin-bottom: 1rem;
  color: #ff6b35;
}

.not-found-page p {
  font-size: 1.5rem;
  margin-bottom: 2rem;
}

.back-home {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background-color: #ff6b35;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  transition: background-color 0.3s ease;
}

.back-home:hover {
  background-color: #ff8c42;
}
</style>
