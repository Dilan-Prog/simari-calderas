import { createRoot } from 'react-dom/client';
import EmailMarketingApp from './email-marketing/EmailMarketingApp';

const el = document.getElementById('email-marketing-root');

if (el) {
  const templatesUrl = el.dataset.templatesUrl;
  const listsUrl = el.dataset.listsUrl;
  const campaignsUrl = el.dataset.campaignsUrl;
  const sequencesUrl = el.dataset.sequencesUrl;
  const userInitials = el.dataset.userInitials;

  const root = createRoot(el);
  root.render(
    <EmailMarketingApp
      templatesUrl={templatesUrl}
      listsUrl={listsUrl}
      campaignsUrl={campaignsUrl}
      sequencesUrl={sequencesUrl}
      userInitials={userInitials}
    />
  );
}
