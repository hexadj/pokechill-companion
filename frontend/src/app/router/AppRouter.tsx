import { Route, Routes } from 'react-router-dom'

import { RecommendationPage } from '../../pages/RecommendationPage/RecommendationPage'

export function AppRouter() {
  return (
    <Routes>
      <Route path="/" element={<RecommendationPage />} />
    </Routes>
  )
}
