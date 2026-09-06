import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  // Dynamic routes with params — must use Client render mode (cannot prerender)
  {
    path: 'admin/application/:id',
    renderMode: RenderMode.Client
  },
  // All Cases — server render
  {
    path: 'admin/all-cases',
    renderMode: RenderMode.Server
  },
  // All other routes use Server render mode
  {
    path: '**',
    renderMode: RenderMode.Server
  }
];

