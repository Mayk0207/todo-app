<template>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold my-4">Todo List</h1>
      <div class="flex gap-2 my-2">
        <input v-model="newTodo" class="border p-2 w-full" placeholder="Add new todo" @keyup.enter="addTodo">
        <button class="bg-blue-500 text-white p-2" @click="addTodo">Add</button>
      </div>
      <ul>
        <li v-for="todo in todos" :key="todo.id" class="flex items-center gap-2">
          <input type="checkbox" v-model="todo.completed" @change="updateTodo(todo)">
          <span :class="{ 'line-through': todo.completed }">{{ todo.title }}</span>
          <button class="ml-auto bg-red-500 text-white p-1" @click="deleteTodo(todo.id)">Delete</button>
        </li>
      </ul>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        todos: [],
        newTodo: ''
      };
    },
    methods: {
      async fetchTodos() {
        const response = await axios.get('/api/todos');
        this.todos = response.data;
      },
      async addTodo() {
        if (this.newTodo === '') return;
  
        const response = await axios.post('/api/todos', { title: this.newTodo });
        this.todos.push(response.data);
        this.newTodo = '';
      },
      async updateTodo(todo) {
        await axios.put(`/api/todos/${todo.id}`, todo);
      },
      async deleteTodo(id) {
        await axios.delete(`/api/todos/${id}`);
        this.todos = this.todos.filter(todo => todo.id !== id);
      }
    },
    mounted() {
      this.fetchTodos();
    }
  };
  </script>
  
  <style scoped>
  .line-through {
    text-decoration: line-through;
  }
  </style>
  