import { UserFormPage } from "../../_components/user-form-page";

export default function EditUserPage({
  params,
}: {
  params: { id: string };
}) {
  return <UserFormPage mode="edit" userId={Number(params.id)} />;
}
