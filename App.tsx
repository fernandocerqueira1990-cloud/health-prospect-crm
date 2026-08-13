import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import { ProtectedRoute } from './components/ProtectedRoute'
import { Layout } from './components/Layout'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Companies from './pages/Companies'
import Placeholder from './pages/Placeholder'

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route element={<ProtectedRoute><Layout /></ProtectedRoute>}>
            <Route index element={<Dashboard />} />
            <Route path="/empresas" element={<Companies />} />
            <Route path="/contatos" element={<Placeholder title="Contatos" />} />
            <Route path="/oportunidades" element={<Placeholder title="Oportunidades" />} />
            <Route path="/tarefas" element={<Placeholder title="Tarefas" />} />
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}
