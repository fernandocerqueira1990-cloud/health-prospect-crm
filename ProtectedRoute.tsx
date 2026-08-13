import { Navigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { session, loading } = useAuth()
  if (loading) return <div className="min-h-screen grid place-items-center">Carregando...</div>
  if (!session) return <Navigate to="/login" replace />
  return <>{children}</>
}
