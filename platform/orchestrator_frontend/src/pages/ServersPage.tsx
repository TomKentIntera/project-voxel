import Header from '../components/Header'

const mockServers = [
  { id: 'mc-0184', owner: 'Alex Martinez', plan: 'SSD-2', cpu: '45%', ram: '1.2 / 2 GB', status: 'running', uptime: '14d 6h' },
  { id: 'mc-0183', owner: 'Jordan Patel', plan: 'SSD-4', cpu: '62%', ram: '2.8 / 4 GB', status: 'running', uptime: '3d 11h' },
  { id: 'mc-0182', owner: 'Taylor Kim', plan: 'SSD-2', cpu: '12%', ram: '0.8 / 2 GB', status: 'running', uptime: '7d 22h' },
  { id: 'mc-0091', owner: 'Sam Wilson', plan: 'SSD-1', cpu: '0%', ram: '0 / 1 GB', status: 'suspended', uptime: '—' },
  { id: 'mc-0180', owner: 'Sarah Chen', plan: 'SSD-2', cpu: '0%', ram: '0 / 2 GB', status: 'stopped', uptime: '—' },
]

const statusColor: Record<string, string> = {
  running: 'bg-emerald-50 text-emerald-700',
  suspended: 'bg-red-50 text-red-700',
  stopped: 'bg-slate-100 text-slate-600',
}

export default function ServersPage() {
  return (
    <>
      <Header title="Servers" description="Game server instances across the platform" />

      <div className="p-8">
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Server ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Owner</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">CPU</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">RAM</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Uptime</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {mockServers.map((server) => (
                <tr key={server.id} className="hover:bg-slate-50">
                  <td className="whitespace-nowrap px-6 py-4 text-sm font-mono font-medium text-slate-900">{server.id}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{server.owner}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{server.plan}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{server.cpu}</td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{server.ram}</td>
                  <td className="whitespace-nowrap px-6 py-4">
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColor[server.status]}`}>
                      {server.status}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{server.uptime}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}

