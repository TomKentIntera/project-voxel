import { useBanner } from '../../hooks/useBanner'

export default function Banner() {
  const { visible, content, isLoading } = useBanner()

  if (isLoading || !visible) return null

  return (
    <div className="call-to-action cta-thin cta-red">
      <div className="custom-width">
        <div className="row">
          <div className="col-sm-12">
            <p dangerouslySetInnerHTML={{ __html: content }} />
          </div>
        </div>
      </div>
    </div>
  )
}
