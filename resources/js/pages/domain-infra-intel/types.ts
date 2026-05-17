export type DomainInfraResult = {
  domain: string;
  ips: string[];
  rdap: { ldhName: string; statuses: string[]; events: Array<Record<string, unknown>>; nameservers: Array<Record<string, unknown>> };
  crtsh: Array<{ issuer: string; nameValue: string; notBefore: string; notAfter: string }>;
  asn: { ip?: string; asn?: string; org?: string; isp?: string; country?: string };
  neighbors: string[];
  error?: string;
};
