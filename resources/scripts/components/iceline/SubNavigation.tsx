import styled from 'styled-components/macro';
import tw, { theme } from 'twin.macro';

const SubNavigation = styled.div`
    ${tw`w-full overflow-x-auto mx-auto`};
    max-width: 1200px;

    & > div {
        ${tw`inline-flex items-center text-sm px-2`};

        border-bottom: 1px solid #323440;

        & > a,
        & > div {
            ${tw`inline-block py-2 px-6 text-neutral-400 no-underline whitespace-nowrap transition-all duration-150`};

            &:active,
            &:hover {
                ${tw`text-neutral-50`};
            }

            &:active,
            &:hover,
            &.active {
                ${tw`text-neutral-50`};
                box-shadow: inset 0 -3px ${theme`colors.icelinePrimary`.toString()};
            }
        }
    }
`;

export default SubNavigation;
