import type { PaginationMeta } from '../lib/usersApi'

interface PaginationProps {
  meta: PaginationMeta
  onPageChange: (page: number) => void
}

export default function Pagination({ meta, onPageChange }: PaginationProps) {
  const { current_page, last_page, total, per_page } = meta

  if (last_page <= 1) return null

  const from = (current_page - 1) * per_page + 1
  const to = Math.min(current_page * per_page, total)

  // Build page numbers to show (max 7 visible)
  const pages = buildPageNumbers(current_page, last_page)

  return (
    <div className="flex items-center justify-between border-t border-slate-200 px-6 py-4">
      <p className="text-sm text-slate-500">
        Showing <span className="font-medium text-slate-700">{from}</span> to{' '}
        <span className="font-medium text-slate-700">{to}</span> of{' '}
        <span className="font-medium text-slate-700">{total}</span> results
      </p>

      <nav className="flex items-center gap-1">
        {/* Previous */}
        <button
          type="button"
          disabled={current_page === 1}
          onClick={() => onPageChange(current_page - 1)}
          className="rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
          </svg>
        </button>

        {/* Page buttons */}
        {pages.map((page, idx) =>
          page === null ? (
            <span
              key={`ellipsis-${idx}`}
              className="px-1 text-sm text-slate-400"
            >
              …
            </span>
          ) : (
            <button
              key={page}
              type="button"
              onClick={() => onPageChange(page)}
              className={`min-w-[2rem] rounded-lg px-2.5 py-1.5 text-sm font-medium transition-colors ${
                page === current_page
                  ? 'bg-indigo-600 text-white'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              {page}
            </button>
          ),
        )}

        {/* Next */}
        <button
          type="button"
          disabled={current_page === last_page}
          onClick={() => onPageChange(current_page + 1)}
          className="rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <svg className="size-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </nav>
    </div>
  )
}

/**
 * Build an array of page numbers with ellipsis (null) for gaps.
 * Always shows first, last, and up to 5 pages around the current page.
 */
function buildPageNumbers(
  current: number,
  last: number,
): (number | null)[] {
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }

  const pages: (number | null)[] = []

  // Always show page 1
  pages.push(1)

  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)

  if (start > 2) pages.push(null)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  if (end < last - 1) pages.push(null)

  // Always show last page
  pages.push(last)

  return pages
}

